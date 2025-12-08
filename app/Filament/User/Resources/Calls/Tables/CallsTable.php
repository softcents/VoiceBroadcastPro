<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calls\Tables;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Filament\User\Resources\Campaigns\CampaignResource;
use App\Models\Call;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use LaraZeus\Tabler\Tabler;

final class CallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                TextColumn::make('duration')
                    ->label('Duration')
                    ->placeholder('-')
                    ->formatStateUsing(fn (int $state) => secondsToHuman($state))
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(CallStatus::class)
                    ->searchable(),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(CallType::class)
                    ->searchable(),
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
