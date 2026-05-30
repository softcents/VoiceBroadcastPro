<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Calling\Calls\Tables;

use App\Enums\CallInterface;
use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Filament\Admin\Resources\Calling\Campaigns\CampaignResource;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Models\Call;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['campaign:id,title', 'user:id,name']))
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('interface')
                    ->label('From')
                    ->badge(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->numeric()
                    ->placeholder('-')
                    ->formatStateUsing(fn (float $state) => secondsToHuman($state))
                    ->sortable(),
                TextColumn::make('cost')
                    ->label('Cost')
                    ->money('BDT', decimalPlaces: 6)
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Call $record) => $record->campaign_id ? CampaignResource::getUrl('view', ['record' => $record->campaign_id]) : null)
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Call $record) => CustomerResource::getUrl('edit', ['record' => $record->user_id]))
                    ->toggleable(),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(CallType::class),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(CallStatus::class),
                SelectFilter::make('interface')
                    ->label('Interface')
                    ->options(CallInterface::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
