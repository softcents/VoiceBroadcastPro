<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\Servers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use LaraZeus\Tabler\Tabler;

final class ServersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('ari_host')
                    ->label('ARI Host')
                    ->searchable(),
                TextColumn::make('database_host')
                    ->label('DB Host')
                    ->searchable(),
                TextColumn::make('connection_status')
                    ->label('Connection Status')
                    ->searchable()
                    ->badge()
                    ->formatStateUsing(fn (string $state) => str($state)->headline()->value())
                    ->color(fn ($state) => match ($state) {
                        'connected' => 'success',
                        'disconnected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'connected' => Tabler::CircleDashedCheck,
                        'disconnected' => Tabler::CircleDashedX,
                        default => Tabler::InfoCircle,
                    }),
                ToggleColumn::make('enabled')
                    ->label('Enabled')
                    ->sortable(),
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
