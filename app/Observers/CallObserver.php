<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CallStatus;
use App\Enums\TransactionType;
use App\Models\Call;

final class CallObserver
{
    /**
     * Handle the Call "created" event.
     */
    public function created(Call $call): void
    {
        if ($call->campaign_id) {
            // Campaign calls are handled in CampaignObserver
            return;
        }

        $call->loadMissing(['user', 'audio']);

        $user = $call->user;
        $audio = $call->audio;

        if (! $user || ! $audio) {
            return;
        }

        // Calculate and reserve cost
        $cost = $audio->calculateCostForUser($user);

        if ($cost > 0) {
            $user->decrement('balance', $cost);

            $user->transactions()->create([
                'type' => TransactionType::Debit,
                'amount' => $cost,
                'currency' => 'BDT',
                'description' => "Reserved balance for call ID {$call->id}",
                'reference_type' => Call::class,
                'reference_id' => $call->id,
            ]);

            $call->cost = $cost;
            $call->saveQuietly();
        }
    }

    /**
     * Handle the Call "updated" event.
     */
    public function updated(Call $call): void
    {
        if ($call->wasChanged('status')) {
            $user = $call->user;

            if (! $user) {
                return;
            }

            // Handle Completion
            if ($call->status === CallStatus::Completed) {
                $pulseDuration = $user->pulse_duration;
                $pulseRate = $user->pulse_rate;

                if ($pulseDuration > 0 && $call->duration > 0) {
                    $pulses = ceil($call->duration / $pulseDuration);
                    $actualCost = $pulses * $pulseRate;
                    $reservedCost = $call->cost;

                    if ($actualCost > $reservedCost) {
                        // Deduct extra
                        $diff = $actualCost - $reservedCost;
                        $user->decrement('balance', $diff);
                        $user->transactions()->create([
                            'type' => TransactionType::Debit,
                            'amount' => $diff,
                            'currency' => 'BDT',
                            'description' => "Additional charge for call ID {$call->id} ({$call->duration}s)",
                            'reference_type' => Call::class,
                            'reference_id' => $call->id,
                        ]);
                    } elseif ($actualCost < $reservedCost) {
                        // Refund difference
                        $diff = $reservedCost - $actualCost;
                        $user->increment('balance', $diff);
                        $user->transactions()->create([
                            'type' => TransactionType::Credit,
                            'amount' => $diff,
                            'currency' => 'BDT',
                            'description' => "Partial refund for call ID {$call->id} ({$call->duration}s)",
                            'reference_type' => Call::class,
                            'reference_id' => $call->id,
                        ]);
                    }

                    $call->cost = $actualCost;
                    $call->saveQuietly();
                }
            }
            // Handle Failure/Busy/NoAnswer/etc where it ends without success but cost was reserved
            elseif (in_array($call->status, [CallStatus::Failed, CallStatus::Busy, CallStatus::NotAnswered])) {
                if ($call->cost > 0) {
                    $refundAmount = $call->cost;
                    $user->increment('balance', $refundAmount);
                    $user->transactions()->create([
                        'type' => TransactionType::Credit,
                        'amount' => $refundAmount,
                        'currency' => 'BDT',
                        'description' => "Full refund for {$call->status->value} call ID {$call->id}",
                        'reference_type' => Call::class,
                        'reference_id' => $call->id,
                    ]);

                    $call->cost = 0;
                    $call->saveQuietly();
                }
            }
        }
    }

    /**
     * Handle the Call "deleted" event.
     */
    public function deleted(Call $call): void
    {
        //
    }

    /**
     * Handle the Call "restored" event.
     */
    public function restored(Call $call): void
    {
        //
    }

    /**
     * Handle the Call "force deleted" event.
     */
    public function forceDeleted(Call $call): void
    {
        //
    }
}
