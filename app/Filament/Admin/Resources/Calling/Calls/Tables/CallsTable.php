<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Calling\Calls\Tables;

use App\Filament\Admin\Resources\Calling\Campaigns\CampaignResource;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Models\Call;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('campaign.title')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Call $record) => $record->campaign ? CampaignResource::getUrl('view', ['record' => $record->campaign_id]) : null),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->url(fn (Call $record) => $record->user ? CustomerResource::getUrl('view', ['record' => $record->user_id]) : null),
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
