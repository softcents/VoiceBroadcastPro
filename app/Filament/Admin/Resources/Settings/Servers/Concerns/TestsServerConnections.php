<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\Servers\Concerns;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

trait TestsServerConnections
{
    protected function testServerConnections(array $data): void
    {
        $errors = [];

        try {
            $ariBaseUrl = "{$data['ari_scheme']}://{$data['ari_host']}:{$data['ari_port']}";

            $response = Http::baseUrl($ariBaseUrl)
                ->withBasicAuth($data['ari_username'], $data['ari_password'])
                ->acceptJson()
                ->timeout(5)
                ->retry(1, 500, fn ($e) => $e instanceof ConnectionException)
                ->get('/ari/asterisk/info');

            if (! $response->successful()) {
                $errors['ari_host'] = 'Could not connect to ARI endpoint.';
            }
        } catch (Throwable $e) {
            $errors['ari_host'] = 'ARI connection failed: '.$e->getMessage();
        }

        try {
            config(['database.connections._server_test' => [
                ...config('database.connections.asterisk'),
                'host' => $data['database_host'],
                'port' => (int) $data['database_port'],
                'username' => $data['database_username'],
                'password' => $data['database_password'],
            ]]);

            DB::purge('_server_test');
            DB::connection('_server_test')->getPdo();
        } catch (Throwable $e) {
            $errors['database_host'] = 'Database connection failed: '.$e->getMessage();
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
