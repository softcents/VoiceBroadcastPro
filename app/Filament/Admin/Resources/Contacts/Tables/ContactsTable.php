<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Contacts\Tables;

use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Phonebooks\PhonebookResource;
use App\Filament\Exports\ContactExporter;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\Tabler\Tabler;

final class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('phonebook.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->url(fn ($record) => CustomerResource::getUrl('view', ['record' => $record->phonebook->user_id])),
                TextColumn::make('phonebook.name')
                    ->searchable()
                    ->url(fn ($record) => PhonebookResource::getUrl('view', ['record' => $record->phonebook_id])),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('phone_number')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                ExportAction::make('export')
                    ->exporter(ContactExporter::class)
                    ->columnMappingColumns(3),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                ExportAction::make('export')
                    ->label('Export')
                    ->icon(Tabler::TableExport)
                    ->exporter(ContactExporter::class)
                    ->columnMappingColumns(3),
            ]);
    }
}
