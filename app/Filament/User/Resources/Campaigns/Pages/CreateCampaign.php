<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Pages;

use App\Enums\CallStatus;
use App\Enums\CampaignSource;
use App\Filament\User\Resources\Campaigns\CampaignResource;
use App\Models\Phonebook;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Propaganistas\LaravelPhone\PhoneNumber;
use RuntimeException;
use Storage;
use Throwable;

final class CreateCampaign extends CreateRecord
{
    protected const int BATCH_SIZE = 500; // Insert calls in batches for better performance

    protected const int MAX_CALLS_PER_CAMPAIGN = 10000; // Prevent memory issues

    protected static string $resource = CampaignResource::class;

    protected ?Collection $preparedCalls = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->preparedCalls = $this->prepareCallsForCampaign($data);

        return $data;
    }

    /**
     * @throws Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $campaign = auth()->user()->campaigns()->create($data);

            try {
                // Use prepared calls or prepare them if strictly necessary (fallback)
                $calls = $this->preparedCalls ?? $this->prepareCallsForCampaign($data);

                // Insert in batches for better performance
                $calls->chunk(self::BATCH_SIZE)->each(function ($chunk) use ($campaign) {
                    $campaign->calls()->createMany($chunk->toArray());
                });

                Notification::make()
                    ->success()
                    ->title('Campaign created successfully')
                    ->body("Created {$calls->count()} calls.")
                    ->send();

            } catch (Throwable $e) {
                // Log the error and provide user-friendly message
                Log::error('Campaign creation failed', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // If DB transaction rolls back, specific errors here might be swallowed if not re-thrown
                // but since we are inside transaction, we want to ensure everything is rolled back.
                throw $e;
            }

            return $campaign;
        });
    }

    /**
     * @throws Throwable
     */
    /**
     * @throws Throwable
     */
    protected function prepareCallsForCampaign(array $data): Collection
    {
        try {
            $calls = match ($data['source']) {
                CampaignSource::Manual => $this->prepareManualCalls($data),
                CampaignSource::Phonebook => $this->preparePhonebookCalls($data),
                CampaignSource::Import => $this->prepareImportCalls($data),
                default => throw new InvalidArgumentException("Invalid campaign source: {$data['source']}"),
            };

            if ($calls->isEmpty()) {
                throw ValidationException::withMessages([
                    'source' => 'No valid phone numbers found. Please check your input.',
                ]);
            }

            // Check for maximum limit
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

    protected function prepareManualCalls(array $data): Collection
    {
        if (empty($data['manual_numbers'])) {
            return collect([]);
        }

        $validCalls = collect();
        $invalidNumbers = [];
        $userId = auth()->id();
        $now = now();

        foreach ($data['manual_numbers'] as $item) {
            $number = mb_trim($item['number'] ?? '');

            if (empty($number)) {
                continue;
            }

            try {
                $normalizedNumber = $this->normalizePhoneNumber($number);

                $validCalls->push([
                    'user_id' => $userId,
                    'phone_number' => $normalizedNumber,
                    'status' => CallStatus::Pending,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (Throwable $e) {
                $invalidNumbers[] = $number;
            }
        }

        // Notify user about invalid numbers
        if (! empty($invalidNumbers)) {
            Notification::make()
                ->warning()
                ->title('Invalid phone numbers detected')
                ->body('Skipped '.count($invalidNumbers).' invalid number(s): '.implode(', ', array_slice($invalidNumbers, 0, 5)))
                ->send();
        }

        // Remove duplicates based on phone_number
        return $validCalls->unique('phone_number')->values();
    }

    /**
     * @throws ValidationException
     */
    protected function preparePhonebookCalls(array $data): Collection
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

            // Check if user has access to this phonebook
            if ($phonebook->user_id !== auth()->id()) {
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
            $invalidCount = 0;
            $userId = auth()->id();
            $now = now();

            foreach ($phonebook->contacts as $contact) {
                try {
                    $normalizedNumber = $this->normalizePhoneNumber($contact->phone_number);

                    $validCalls->push([
                        'user_id' => $userId,
                        'contact_id' => $contact->id,
                        'phone_number' => $normalizedNumber,
                        'contact_name' => mb_trim($contact->first_name.' '.$contact->last_name) ?: null,
                        'status' => CallStatus::Pending,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } catch (Throwable $e) {
                    $invalidCount++;
                    Log::warning('Invalid phone number in phonebook', [
                        'contact_id' => $contact->id,
                        'phone_number' => $contact->phone_number,
                    ]);
                }
            }

            if ($invalidCount > 0) {
                Notification::make()
                    ->warning()
                    ->title('Invalid contacts detected')
                    ->body("Skipped {$invalidCount} contact(s) with invalid phone numbers.")
                    ->send();
            }

            return $validCalls->unique('phone_number')->values();

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error preparing phonebook calls', [
                'phonebook_id' => $data['phonebook_id'],
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'phonebook_id' => 'Failed to load phonebook contacts: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function prepareImportCalls(array $data): Collection
    {
        if (empty($data['file_path'])) {
            throw ValidationException::withMessages([
                'file_path' => 'Import file is required.',
            ]);
        }

        $filePath = $data['file_path'];

        try {
            $fullPath = Storage::disk('local')->path($filePath);

            if (! file_exists($fullPath)) {
                throw new RuntimeException('Import file not found.');
            }

            if (! is_readable($fullPath)) {
                throw new RuntimeException('Import file is not readable.');
            }

            // Check file size (max 10MB to prevent memory issues)
            $maxFileSize = 10 * 1024 * 1024; // 10MB
            if (filesize($fullPath) > $maxFileSize) {
                throw ValidationException::withMessages([
                    'file_path' => 'File is too large. Maximum size is 10MB.',
                ]);
            }

            $numbers = [];
            $lineNumber = 0;
            $invalidCount = 0;
            $userId = auth()->id();
            $now = now();

            $handle = fopen($fullPath, 'r');

            if ($handle === false) {
                throw new RuntimeException('Could not open import file.');
            }

            try {
                // Skip header row
                fgetcsv($handle, 1000, ',');
                $lineNumber++;

                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    $lineNumber++;

                    // Check if we've hit the maximum
                    if (count($numbers) >= self::MAX_CALLS_PER_CAMPAIGN) {
                        Notification::make()
                            ->warning()
                            ->title('Import limit reached')
                            ->body('Only the first '.self::MAX_CALLS_PER_CAMPAIGN.' numbers were imported.')
                            ->send();
                        break;
                    }

                    if (empty($row[0])) {
                        continue;
                    }

                    $number = mb_trim($row[0]);

                    if ($number === '') {
                        continue;
                    }

                    try {
                        $normalizedNumber = $this->normalizePhoneNumber($number);
                        $numbers[] = [
                            'user_id' => $userId,
                            'phone_number' => $normalizedNumber,
                            'status' => CallStatus::Pending,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    } catch (Throwable $e) {
                        $invalidCount++;
                        if ($invalidCount <= 10) { // Log only first 10 invalid numbers
                            Log::warning('Invalid phone number in import', [
                                'line' => $lineNumber,
                                'number' => $number,
                            ]);
                        }
                    }
                }
            } finally {
                fclose($handle);
            }

            if ($invalidCount > 0) {
                Notification::make()
                    ->warning()
                    ->title('Invalid numbers detected')
                    ->body("Skipped {$invalidCount} invalid phone number(s) from the import file.")
                    ->send();
            }

            // Remove duplicates
            return collect($numbers)->unique('phone_number')->values();

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error importing calls', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'file_path' => 'Failed to import file: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function normalizePhoneNumber(string $number): string
    {
        try {
            // Remove common formatting characters
            $cleaned = preg_replace('/[\s\-\(\)\.]+/', '', $number);

            if (empty($cleaned)) {
                throw new InvalidArgumentException('Empty phone number');
            }

            // Try to format as E164
            $phoneNumber = new PhoneNumber($cleaned);

            return $phoneNumber->formatE164();

        } catch (Throwable $e) {
            throw new InvalidArgumentException("Invalid phone number format: {$number}");
        }
    }
}
