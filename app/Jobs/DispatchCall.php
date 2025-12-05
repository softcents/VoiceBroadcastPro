<?php

namespace App\Jobs;

use App\Models\Campaign;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchCall implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign,
        public string $phoneNumber
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mock sending call
        // In a real scenario, this would interact with Asterisk or a VoIP provider
        // For now, we just create a Call record

        $this->campaign->calls()->create([
            'user_id' => $this->campaign->user_id,
            'phone_number' => $this->phoneNumber,
            'status' => \App\Enums\CallStatus::Initiated,
            'content' => 'Campaign Call: ' . $this->campaign->title,
        ]);

        // Simulate some processing time
        // sleep(1);
    }
}
