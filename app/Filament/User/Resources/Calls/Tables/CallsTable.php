<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calls\Tables;

use App\Enums\CallStatus;
use App\Enums\CallType;
use App\Jobs\ProcessMarketingCall;
use App\Jobs\ProcessOtpCall;
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
                Action::make('retry')
                    ->label('Retry')
                    ->icon(Tabler::Refresh)
                    ->color('danger')
                    ->visible(fn (Call $record) => $record->status === CallStatus::Failed)
                    ->requiresConfirmation()
                    ->action(function (Call $record) {
                        $record->type === CallType::Marketing
                            ? ProcessMarketingCall::dispatch($record->id)
                            : ProcessOtpCall::dispatch($record->id);
                    }),
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
                                ->each(function (Call $record) {
                                    $record->type === CallType::Marketing
                                        ? ProcessMarketingCall::dispatch($record->id)
                                        : ProcessOtpCall::dispatch($record->id);
                                });
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
