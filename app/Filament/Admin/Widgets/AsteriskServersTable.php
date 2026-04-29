<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Server;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

final class AsteriskServersTable extends BaseWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Server::query()
                    ->orderBy('enabled', 'desc')
                    ->orderBy('host')
            )
            ->columns([
                TextColumn::make('host')
                    ->label('Server')
                    ->description(fn (Server $record): string => $record->ari_base_url)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('enabled')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Enabled' : 'Disabled'),

                TextColumn::make('connection_status')
                    ->label('Connection')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'connected' => 'success',
                        'disconnected' => 'gray',
                        'error' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'connected' => 'Connected',
                        'disconnected' => 'Disconnected',
                        'error' => 'Error',
                        default => 'Unknown',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'connected' => 'heroicon-m-check-circle',
                        'disconnected' => 'heroicon-m-x-circle',
                        'error' => 'heroicon-m-exclamation-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                TextColumn::make('connected_at')
                    ->label('Last Connected')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('disconnected_at')
                    ->label('Last Disconnected')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->heading('Server Connections')
            ->description('Real-time status of Asterisk ARI server connections');
    }
}
