<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Phonebooks\Pages;

use App\Filament\User\Resources\Contacts\ContactResource;
use App\Filament\User\Resources\Phonebooks\PhonebookResource;
use App\Models\Phonebook;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use LaraZeus\Tabler\Tabler;

final class ViewPhonebook extends ViewRecord
{
    protected static string $resource = PhonebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //            ExcelImportAction::make()
            //                ->sampleExcel(
            //                    sampleData: [
            //                        ['first_name' => 'Bishwajit', 'last_name' => 'Adhikary', 'phone' => '+8801322635808'],
            //                        ['first_name' => 'John', 'last_name' => 'Doe', 'phone' => '+8801608460717'],
            //                    ],
            //                    fileName: 'sample.xlsx',
            //                    sampleButtonLabel: 'Download Sample',
            //                    customiseActionUsing: fn(Action $action) => $action->color('secondary')
            //                        ->icon('heroicon-m-clipboard')
            //                        ->requiresConfirmation(),
            //                ),

            //            Action::make('import')
            //                ->icon(Tabler::TableImport)
            //                ->label('Import')
            //                ->outlined()
            //                ->modalHeading('Import Contacts')
            //                ->modalDescription('Import contacts into this phonebook by uploading a file.')
            //                ->schema([
            //                    Section::make()
            //                        ->schema([
            //                            FileUpload::make('file')
            //                                ->label('File')
            //                                ->required()
            //                                ->acceptedFileTypes([
            //                                    'text/csv',
            //                                    'application/vnd.ms-excel',
            //                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            //                                    'application/vnd.oasis.opendocument.spreadsheet',
            //                                ])
            //                                ->maxSize(1024)
            //                                ->helperText('Upload a CSV, XLS, XLSX, or ODS file with phone numbers to import into the phonebook.'),
            //                        ])
            //                ]),
            EditAction::make(),
            Action::make('create_contact')
                ->label('Add Contact')
                ->url(fn (Phonebook $record) => ContactResource::getUrl('create', ['phonebook_id' => $record->id])),
        ];
    }
}
