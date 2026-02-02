<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Audio;
use App\Models\Phonebook;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Number;
use Illuminate\Translation\PotentiallyTranslatedString;

final class EnsureUserHasSufficientBalanceForCampaign implements ValidationRule
{
    public function __construct(protected ?int $phonebookId = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $audio = Audio::find($value);
        if (! $audio) {
            return;
        }

        if ($this->phonebookId === null) {
            return;
        }

        $phonebook = Phonebook::withCount('contacts')->find($this->phonebookId);
        if (! $phonebook) {
            return;
        }

        $contactsCount = (int) $phonebook->contacts_count;
        if ($contactsCount === 0) {
            return;
        }

        $costPerCall = $audio->calculateCostForUser($user);
        $totalEstimatedCost = $costPerCall * $contactsCount;

        if (! $user->hasEnoughBalance($totalEstimatedCost)) {
            $message = sprintf(
                'Insufficient balance. This campaign costs estimated %s for %s calls. Your balance: %s.',
                Number::currency($totalEstimatedCost, 'BDT'),
                Number::format($contactsCount),
                Number::currency($user->balance, 'BDT')
            );

            $fail($message);
        }
    }
}
