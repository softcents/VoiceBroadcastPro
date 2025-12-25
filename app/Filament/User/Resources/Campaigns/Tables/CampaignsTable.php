<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Tables;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width(0)
                    ->alignCenter(),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(20),
                TextColumn::make('audio.title')
                    ->label('Audio')
                    ->limit(20),
                TextColumn::make('phonebook.name')
                    ->label('Phonebook')
                    ->limit(20),
                TextColumn::make('status')
                    ->label('Current Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->name),
                TextColumn::make('scheduled_at')
                    ->label('Scheduled At')
                    ->placeholder('Not Scheduled')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
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
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (Campaign $record) => $record->status === CampaignStatus::Pending && $record->scheduled_at),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
