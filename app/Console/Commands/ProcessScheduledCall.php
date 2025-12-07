<?php

namespace App\Console\Commands;

use App\Enums\CallStatus;
use App\Jobs\ProcessCall;
use App\Models\Call;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ProcessScheduledCall extends Command
{
    protected $signature = 'app:process-scheduled-call
                            {--limit=50 : Maximum number of calls to process per chunk}
                            {--delay=10 : Delay in seconds between chunks}';

    protected $description = 'Process scheduled calls with rate limiting';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');

        $this->info('Starting scheduled call processing...');

        $processedChunks = 0;
        $totalCalls = 0;

        try {
            Call::where('status', CallStatus::Pending)
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', now())
                ->chunkById($limit, function ($calls) use ($delay, &$processedChunks, &$totalCalls) {

                    RateLimiter::attempt(
                        'process-scheduled-calls',
                        $maxAttempts = 1,
                        function () use ($calls, &$processedChunks, &$totalCalls) {
                            DB::transaction(function () use ($calls) {
                                $callIds = $calls->pluck('id');

                                DB::table('calls')
                                    ->whereIn('id', $callIds)
                                    ->update([
                                        'status' => CallStatus::Initiated,
                                        'initiated_at' => now()
                                    ]);

                                $jobs = $calls->map(fn($call) => new ProcessCall($call->id));

                                $batch = Bus::batch($jobs->toArray())
                                    ->name('Scheduled Calls - ' . now()->format('Y-m-d H:i:s'))
                                    ->allowFailures()
                                    ->dispatch();

                                Log::info('Scheduled calls batch dispatched', [
                                    'batch_id' => $batch->id,
                                    'call_count' => $calls->count()
                                ]);
                            });

                            $processedChunks++;
                            $totalCalls += $calls->count();

                            $this->info("Processed chunk {$processedChunks}: {$calls->count()} calls");
                        },
                        $decaySeconds = $delay
                    );
                });

            if ($totalCalls === 0) {
                $this->info('No scheduled calls found to process.');
                return self::SUCCESS;
            }

            $this->info("✓ Successfully processed {$totalCalls} calls in {$processedChunks} chunks");

            Log::info('Scheduled call processing completed', [
                'total_calls' => $totalCalls,
                'chunks' => $processedChunks
            ]);

            return self::SUCCESS;

        } catch (\Throwable $exception) {
            $this->error('Failed to process scheduled calls: ' . $exception->getMessage());

            Log::error('Scheduled call processing failed', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            return self::FAILURE;
        }
    }
}
