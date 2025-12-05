<?php

namespace App\Jobs;

use App\Enums\CampaignSource;
use App\Models\Campaign;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class ProcessCampaign implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign,
        public ?array $phoneNumbers = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobs = [];

        if ($this->campaign->source === CampaignSource::Phonebook) {
            $this->campaign->phonebook->contacts()->chunk(1000, function ($contacts) use (&$jobs) {
                foreach ($contacts as $contact) {
                    $jobs[] = new DispatchCall($this->campaign, $contact->phone_number);
                }
            });
        } elseif ($this->campaign->source === CampaignSource::Manual) {
            foreach ($this->phoneNumbers ?? [] as $phoneNumber) {
                $jobs[] = new DispatchCall($this->campaign, $phoneNumber);
            }
        } elseif ($this->campaign->source === CampaignSource::Import) {
            if ($this->campaign->file_path && Storage::exists($this->campaign->file_path)) {
                $path = Storage::path($this->campaign->file_path);
                $handle = fopen($path, 'r');
                $header = fgetcsv($handle); // Skip header if exists, assuming 'phone' column or first column

                while (($row = fgetcsv($handle)) !== false) {
                    // Assuming first column is phone number if no header mapping logic yet
                    $phoneNumber = $row[0] ?? null;
                    if ($phoneNumber) {
                        $jobs[] = new DispatchCall($this->campaign, $phoneNumber);
                    }
                }
                fclose($handle);
            }
        }

        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->name('Campaign: ' . $this->campaign->title)
                ->onQueue('campaigns')
                ->dispatch();
        }
    }
}
