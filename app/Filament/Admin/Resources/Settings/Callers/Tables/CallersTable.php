<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\Callers\Tables;

use App\Filament\Admin\Resources\Settings\Servers\ServerResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

final class CallersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->url(fn ($record) => ServerResource::getUrl('edit', ['record' => $record->server_id])),
                TextColumn::make('caller_name')
                    ->label('Caller Name')
                    ->searchable(),
                TextColumn::make('caller_number')
                    ->label('Caller Number')
                    ->searchable(),
                TextColumn::make('max_concurrency')
                    ->label('Max Concurrency')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Unlimited' : trans_choice(':count Call|:count Calls', $state, ['count' => $state])),
                TextColumn::make('users_count')
                    ->label('Assigned')
                    ->counts('users')
                    ->alignCenter()
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'None' : trans_choice(':count User|:count Users', $state, ['count' => $state])),
                ToggleColumn::make('enabled')
                    ->label('Enabled')
                    ->sortable()
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
