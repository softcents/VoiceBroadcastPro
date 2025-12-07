<?php

namespace App\Filament\Admin\Resources\Callers\Tables;

use App\Filament\Admin\Resources\Servers\ServerResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CallersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->url(fn($record) => ServerResource::getUrl('edit', ['record' => $record->server_id])),
                TextColumn::make('caller_name')
                    ->label('Caller Name')
                    ->searchable(),
                TextColumn::make('caller_number')
                    ->label('Caller Number')
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label('Assigned')
                    ->counts('users')
                    ->alignCenter(),
                IconColumn::make('enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
