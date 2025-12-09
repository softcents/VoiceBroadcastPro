<?php

declare(strict_types=1);

namespace App\Actions\Campaign;

use App\Enums\CallStatus;
use App\Enums\CampaignSource;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Phonebook;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Propaganistas\LaravelPhone\PhoneNumber;
use RuntimeException;
use Throwable;

final class CreateCampaignAction
{
    private const int BATCH_SIZE = 500;

    private const int MAX_CALLS_PER_CAMPAIGN = 10000;

    /**
     * @throws Throwable
     */
    public function execute(User $user, array $data): Campaign
    {
        // 1. Prepare calls first to validate potential issues before creating campaign
        $calls = $this->prepareCallsForCampaign($user, $data);

        return DB::transaction(function () use ($user, $data, $calls) {
            // 2. Create the Campaign
            $campaign = $user->campaigns()->create([
                'audio_id' => $data['audio_id'],
                'phonebook_id' => $data['phonebook_id'] ?? null,
                'caller_id' => $data['caller_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'source' => $data['source'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'status' => CampaignStatus::Pending,
            ]);

            // 3. Save file path if present (for import source mostly, but API might handle file differently)
            if (isset($data['file_path'])) {
                // Logic to handle file storage details if needed in campaign model?
                // Currently Campaign model doesn't seem to store file_path in fillable shown in Controller?
                // Controller code: '$data['file_path'] = $path;' -> then create($data).
                // Filament code: doesn't explicitly save 'file_path' to campaign, but uses it to generating calls.
                // Let's assume for now we just care about creating calls.
            }

            // 4. Insert Calls
            try {
                $calls->chunk(self::BATCH_SIZE)->each(function ($chunk) use ($campaign) {
                    $campaign->calls()->createMany($chunk->toArray());
                });
            } catch (Throwable $e) {
                Log::error('Campaign calls creation failed', [
                    'user_id' => $user->id,
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            return $campaign;
        });
    }

    /**
     * @throws Throwable
     */
    public function prepareCallsForCampaign(User $user, array $data): Collection
    {
        try {
            $source = $data['source'] instanceof CampaignSource
                ? $data['source']
                : CampaignSource::tryFrom($data['source']);

            if (! $source) {
                throw new InvalidArgumentException("Invalid campaign source: {$data['source']}");
            }

            $calls = match ($source) {
                CampaignSource::Manual => $this->prepareManualCalls($user, $data),
                CampaignSource::Phonebook => $this->preparePhonebookCalls($user, $data),
                CampaignSource::Import => $this->prepareImportCalls($user, $data),
            };

            if ($calls->isEmpty()) {
                throw ValidationException::withMessages([
                    'source' => 'No valid phone numbers found. Please check your input.',
                ]);
            }

            if ($calls->count() > self::MAX_CALLS_PER_CAMPAIGN) {
                throw ValidationException::withMessages([
                    'source' => 'Too many calls. Maximum allowed is '.self::MAX_CALLS_PER_CAMPAIGN.' per campaign.',
                ]);
            }

            return $calls;

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error preparing calls', [
                'source' => $data['source'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function prepareManualCalls(User $user, array $data): Collection
    {
        // Support both Filament format (array of keyed arrays) and API format (simple array of strings or keyed)
        // Filament: 'manual_numbers' => [['number' => '...+'], ...]
        // API: 'phone_numbers' => ['...', '...']

        $numbersInput = $data['manual_numbers'] ?? $data['phone_numbers'] ?? [];

        if (empty($numbersInput)) {
            return collect([]);
        }

        $validCalls = collect();
        $invalidNumbers = [];
        $now = now();

        foreach ($numbersInput as $item) {
            // Handle both array/object wrapper and direct string
            $rawNumber = is_array($item) ? ($item['number'] ?? '') : $item;
            $number = mb_trim((string) $rawNumber);

            if (empty($number)) {
                continue;
            }

            try {
                $normalizedNumber = $this->normalizePhoneNumber($number);

                $validCalls->push([
                    'user_id' => $user->id,
                    'audio_id' => $data['audio_id'],
                    'caller_id' => $data['caller_id'] ?? null,
                    'phone_number' => $normalizedNumber,
                    'status' => CallStatus::Pending,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (Throwable $e) {
                $invalidNumbers[] = $number;
            }
        }

        // We might want to return invalid numbers info, but for now Action just returns valid ones.
        // The calling controller/page can handle notifications if needed?
        // Or we can throw exception if strictly strict?
        // Filament implementation notifies but proceeds with valid ones.

        return $validCalls->unique('phone_number')->values();
    }

    private function preparePhonebookCalls(User $user, array $data): Collection
    {
        if (empty($data['phonebook_id'])) {
            throw ValidationException::withMessages([
                'phonebook_id' => 'Phonebook ID is required.',
            ]);
        }

        try {
            $phonebook = Phonebook::with(['contacts' => function ($query) {
                $query->select('id', 'phonebook_id', 'phone_number', 'first_name', 'last_name')
                    ->whereNotNull('phone_number')
                    ->where('phone_number', '!=', '');
            }])->findOrFail($data['phonebook_id']);

            if ($phonebook->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'phonebook_id' => 'You do not have access to this phonebook.',
                ]);
            }

            if ($phonebook->contacts->isEmpty()) {
                throw ValidationException::withMessages([
                    'phonebook_id' => 'The selected phonebook has no valid contacts.',
                ]);
            }

            $validCalls = collect();
            $now = now();

            foreach ($phonebook->contacts as $contact) {
                try {
                    $normalizedNumber = $this->normalizePhoneNumber($contact->phone_number);

                    $validCalls->push([
                        'user_id' => $user->id,
                        'contact_id' => $contact->id,
                        'audio_id' => $data['audio_id'],
                        'caller_id' => $data['caller_id'] ?? null,
                        'phone_number' => $normalizedNumber,
                        'status' => CallStatus::Pending,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } catch (Throwable $e) {
                    // Skip invalid
                }
            }

            return $validCalls->unique('phone_number')->values();

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'phonebook_id' => 'Failed to load phonebook contacts: '.$e->getMessage(),
            ]);
        }
    }

    private function prepareImportCalls(User $user, array $data): Collection
    {
        if (empty($data['file_path'])) {
            throw ValidationException::withMessages([
                'file_path' => 'Import file is required.',
            ]);
        }

        $filePath = $data['file_path'];
        $fullPath = is_file($filePath) ? $filePath : Storage::disk('local')->path($filePath);
        // API might pass raw file content or temp path?
        // Filament passes path relative to storage usually.

        if (! file_exists($fullPath)) {
            // Try storage path if direct path fails
            if (Storage::exists($filePath)) {
                $fullPath = Storage::path($filePath);
            } else {
                throw new RuntimeException('Import file not found.');
            }
        }

        $numbers = [];
        $now = now();

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            throw new RuntimeException('Could not open import file.');
        }

        try {
            // Skip header
            fgetcsv($handle, 1000, ',');

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($numbers) >= self::MAX_CALLS_PER_CAMPAIGN) {
                    break;
                }

                $number = mb_trim($row[0] ?? '');
                if ($number === '') {
                    continue;
                }

                try {
                    $normalizedNumber = $this->normalizePhoneNumber($number);
                    $numbers[] = [
                        'user_id' => $user->id,
                        'audio_id' => $data['audio_id'],
                        'caller_id' => $data['caller_id'] ?? null,
                        'phone_number' => $normalizedNumber,
                        'status' => CallStatus::Pending,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } catch (Throwable $e) {
                    // skip
                }
            }
        } finally {
            fclose($handle);
        }

        return collect($numbers)->unique('phone_number')->values();
    }

    private function normalizePhoneNumber(string $number): string
    {
        // Simple regex cleanup
        $cleaned = preg_replace('/[\s\-\(\)\.]+/', '', $number);

        if (empty($cleaned)) {
            throw new InvalidArgumentException('Empty phone number');
        }

        try {
            return new PhoneNumber($cleaned)->formatE164();
        } catch (Throwable $e) {
            throw new InvalidArgumentException("Invalid phone number format: {$number}");
        }
    }
}
