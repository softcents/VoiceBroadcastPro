<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Campaigns\Tables;

use App\Enums\CampaignApproval;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use LaraZeus\Tabler\Tabler;

final class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->url(fn (Campaign $record) => CustomerResource::getUrl('view', ['record' => $record->user_id])),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->wrap()
                    ->limit(50),
                TextColumn::make('approval')
                    ->label('Approval')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->name),
                TextColumn::make('status')
                    ->label('Current Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->name),
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
                    EditAction::make(),
                    Action::make('approve')
                        ->label('Approve')
                        ->icon(Tabler::CircleCheck)
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Campaign $record) => $record->approval !== CampaignApproval::Approved)
                        ->action(fn (Campaign $record) => $record->update(['approval' => CampaignApproval::Approved])),
                    Action::make('reject')
                        ->label('Reject')
                        ->icon(Tabler::CircleX)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Campaign $record) => $record->approval !== CampaignApproval::Rejected)
                        ->action(fn (Campaign $record) => $record->update(['approval' => CampaignApproval::Rejected])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon(Tabler::CircleCheck)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(fn (Campaign $record) => $record->update(['approval' => CampaignApproval::Approved]))),
                    BulkAction::make('reject')
                        ->label('Reject Selected')
                        ->icon(Tabler::CircleX)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(fn (Campaign $record) => $record->update(['approval' => CampaignApproval::Rejected]))),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
