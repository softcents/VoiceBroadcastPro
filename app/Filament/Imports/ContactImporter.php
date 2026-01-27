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
                ->rules(['required', 'max:255', 'phone:BD'])
                ->fillRecordUsing(function (Contact $record, string $state) {
                    $record->phone_number = rescue(function () use ($state) {
                        return new PhoneNumber($state, 'BD')->formatE164();
                    }, null, false);

                }),
        ];
    }

    public function resolveRecord(): Contact|Model
    {
        return Contact::firstOrNew([
            'phone_number' => rescue(function () {
                return new PhoneNumber($this->data['phone_number'], 'BD')->formatE164();
            }, null, false),
            'phonebook_id' => $this->options['phonebook_id']
        ]);
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
