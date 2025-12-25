<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calls\Tables;

use App\Enums\CallFromInterface;
use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use LaraZeus\Tabler\Tabler;

final class CallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultSort('created_at', direction: 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width(0)
                    ->alignCenter(),
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
                TextColumn::make('from_interface')
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
                    ->money('BDT')
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
                SelectFilter::make('from_interface')
                    ->label('From Interface')
                    ->options(CallFromInterface::class)
                    ->multiple()
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label('Retry')
                    ->icon(Tabler::RepeatOnce)
                    ->color('danger')
                    ->visible(fn (Call $record) => $record->can_retry)
                    ->requiresConfirmation()
                    ->action(fn (Call $record) => $record->retry()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('retry')
                        ->label('Retry selected')
                        ->icon(Tabler::Refresh)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->where('status', CallStatus::Failed)
                                ->each(fn (Call $record) => $record->retry());
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
