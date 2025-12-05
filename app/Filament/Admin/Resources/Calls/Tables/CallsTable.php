<?php

namespace App\Filament\Admin\Resources\Calls\Tables;

use App\Enums\CallStatus;
use App\Filament\Admin\Resources\Campaigns\CampaignResource;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaraZeus\Tabler\Tabler;

class CallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('phone_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (\App\Models\Call $record) => match ($record->status) {
                        CallStatus::Answered => 'success',
                        CallStatus::Ringing, CallStatus::Initiated => 'info',
                        CallStatus::Busy, CallStatus::Failed => 'danger',
                        CallStatus::Pending, CallStatus::NotAnswered => 'warning',
                        CallStatus::Cancelled => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (\App\Models\Call $record) => match ($record->status) {
                        CallStatus::Pending => Tabler::Clock,
                        CallStatus::Initiated => Tabler::PlayerPlay,
                        CallStatus::Ringing => Tabler::PhoneCalling,
                        CallStatus::Answered => Tabler::PhoneCheck,
                        CallStatus::Busy => Tabler::PhoneX,
                        CallStatus::NotAnswered => Tabler::PhoneOff,
                        CallStatus::Failed => Tabler::AlertCircle,
                        CallStatus::Cancelled => Tabler::X,
                        default => Tabler::Help,
                    }),
                TextColumn::make('campaign.title')
                    ->searchable()
                    ->sortable()
                    ->url(fn (\App\Models\Call $record) => CampaignResource::getUrl('view', ['record' => $record->campaign_id])),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->url(fn (\App\Models\Call $record) => CustomerResource::getUrl('view', ['record' => $record->user_id])),
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
            ->recordActions([

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
