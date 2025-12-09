<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Tables;

use App\Enums\CampaignSource;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\Tabler\Tabler;

final class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->wrap()
                    ->limit(50),
                TextColumn::make('status')
                    ->label('Current Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->name)
                    ->icon(fn (Campaign $record) => match ($record->status) {
                        CampaignStatus::Pending,
                        CampaignStatus::Cancelled => Tabler::Clock,
                        CampaignStatus::Processing => Tabler::Refresh,
                        CampaignStatus::Completed => Tabler::Check,
                        CampaignStatus::Failed => Tabler::X,
                    })
                    ->color(fn (Campaign $record) => match ($record->status) {
                        CampaignStatus::Pending,
                        CampaignStatus::Cancelled => 'warning',
                        CampaignStatus::Processing => 'primary',
                        CampaignStatus::Completed => 'success',
                        CampaignStatus::Failed => 'danger',
                    }),
                TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->name)
                    ->color(fn ($state) => match ($state) {
                        CampaignSource::Phonebook => 'success',
                        CampaignSource::Manual => 'primary',
                        CampaignSource::Import => 'secondary',
                    })
                    ->icon(fn ($state) => match ($state) {
                        CampaignSource::Phonebook => Tabler::AddressBook,
                        CampaignSource::Manual => Tabler::Writing,
                        CampaignSource::Import => Tabler::FileImport,
                    }),
                TextColumn::make('scheduled_at')
                    ->label('Scheduled At')
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
