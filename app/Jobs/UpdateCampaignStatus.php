<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallStatus;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class UpdateCampaignStatus implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 60;

    public function __construct(public readonly int $campaignId) {}

    public function uniqueId(): string
    {
        return (string) $this->campaignId;
    }

    public function handle(): void
    {
        $campaign = Campaign::query()
            ->withoutGlobalScopes()
            ->with('calls')
            ->find($this->campaignId);

        if (! $campaign) {
            return;
        }

        // Already in a terminal state — nothing to do.
        if (in_array($campaign->status, [CampaignStatus::Finished, CampaignStatus::Failed, CampaignStatus::Cancelled], true)) {
            return;
        }

        $calls = $campaign->calls;

        if ($calls->isEmpty()) {
            return;
        }

        $total = $calls->count();
        $completed = $calls->where('status', CallStatus::Completed)->count();
        $failed = $calls->where('status', CallStatus::Failed)->count();
        $processing = $calls->where('status', CallStatus::Processing)->count();
        $pending = $calls->where('status', CallStatus::Pending)->count();
        $initiated = $calls->where('status', CallStatus::Initiated)->count();

        if ($processing > 0 || $pending > 0 || $initiated > 0) {
            // Still has active or queued calls — at least set Processing if not already.
            if ($campaign->status !== CampaignStatus::Processing) {
                $campaign->update(['status' => CampaignStatus::Processing]);
            }

            return;
        }

        // All calls are terminal. If we got here, no Pending or Processing remain.
        $terminal = $completed + $failed;

        if ($terminal === $total) {
            $newStatus = $completed > 0
                ? CampaignStatus::Finished
                : CampaignStatus::Failed;

            if ($campaign->status !== $newStatus) {
                $campaign->update(['status' => $newStatus]);
            }
        }
    }
}
