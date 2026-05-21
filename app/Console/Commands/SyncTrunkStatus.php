<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Caller;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

#[Signature('app:sync-trunk-status')]
#[Description('Command description')]
final class SyncTrunkStatus extends Command
{
    /**
     * Execute the console command.
     *
     * @throws ConnectionException
     */
    public function handle(): void
    {
        /** @var Collection $srvGroups */
        $srvGroups = Caller::syncable()
            ->whereRelation('server', 'enabled', true)
            ->with('server')
            ->get()
            ->groupBy('server_id');

        foreach ($srvGroups as $srvId => $callers) {
            $server = $callers->first()->server;

            /** @var Response|null $response */
            $response = rescue(fn () => $server->httpClient()->get('ari/endpoints'));

            if (! $response?->successful()) {
                $this->error("Failed to fetch endpoints from server ID {$srvId}");

                continue;
            }

            $endpointMap = collect($response->json() ?? [])
                ->mapWithKeys(fn ($ep) => [
                    ($ep['resource'] ?? '') => ($ep['state'] ?? null) === 'online',
                ])
                ->toArray();

            $updates = $callers
                ->filter(fn ($caller) => $caller->is_online !== ($endpointMap[$caller->caller_number] ?? false))
                ->map(fn ($caller) => [
                    'id' => $caller->id,
                    'is_online' => $endpointMap[$caller->caller_number] ?? false,
                    'last_synced_at' => now(),
                ])
                ->values()
                ->toArray();

            if (! empty($updates)) {
                $now = now();

                $onlineIds = collect($updates)
                    ->where('is_online', true)
                    ->pluck('id')
                    ->toArray();

                $offlineIds = collect($updates)
                    ->where('is_online', false)
                    ->pluck('id')
                    ->toArray();

                if (! empty($onlineIds)) {
                    Caller::whereIn('id', $onlineIds)->update([
                        'is_online' => true,
                        'last_synced_at' => $now,
                    ]);
                }

                if (! empty($offlineIds)) {
                    Caller::whereIn('id', $offlineIds)->update([
                        'is_online' => false,
                        'last_synced_at' => $now,
                    ]);
                }

                $this->info(count($updates)." callers updated for server {$srvId}");
            }
        }
    }
}
