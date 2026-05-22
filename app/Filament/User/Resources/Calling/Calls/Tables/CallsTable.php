<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calling\Calls\Tables;

use App\Enums\CallInterface;
use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Models\Call;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use LaraZeus\Tabler\Tabler;
use Throwable;

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
                SelectFilter::make('interface')
                    ->label('Interface')
                    ->options(CallInterface::class)
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
                        ->action(function (Collection $records): void {
                            $skipped = 0;

                            $records->each(function (Call $record) use (&$skipped): void {
                                try {
                                    $record->retry();
                                } catch (Throwable) {
                                    $skipped++;
                                }
                            });

                            if ($skipped > 0) {
                                Notification::make()
                                    ->title("{$skipped} call(s) could not be retried")
                                    ->body('Calls may have insufficient balance or exceeded the retry limit.')
                                    ->warning()
                                    ->send();
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
