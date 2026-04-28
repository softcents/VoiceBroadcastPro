<?php

namespace App\Filament\Imports;

use App\Models\Contact;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Propaganistas\LaravelPhone\PhoneNumber;

class ContactImporter extends Importer
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('first_name')
                ->label('First Name')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('last_name')
                ->label('Last Name')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('phone_number')
                ->label('Phone Number')
                ->requiredMapping()
                ->rules(['required', 'max:255', 'phone:BD']),
        ];
    }

    public function resolveRecord(): Contact|Model
    {
        $phone = rescue(
            fn () => new PhoneNumber($this->data['phone_number'], 'BD')->formatE164(),
            null,
            false
        );

        $this->data['phone_number'] = $phone;

        return Contact::firstOrNew([
            'phonebook_id' => $this->options['phonebook_id'],
            'phone_number' => $phone,
        ]);
    }

    public static function getChunkSize(): int
    {
        return 1000;
    }

    public function getJobBatchSize(): ?int
    {
        return 500;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your contact import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
