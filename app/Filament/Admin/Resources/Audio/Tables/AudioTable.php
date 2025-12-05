<?php

namespace App\Filament\Admin\Resources\Audio\Tables;

use App\Enums\AudioApproval;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Models\Audio;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class AudioTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->url(fn(Audio $record) => CustomerResource::getUrl('view', ['record' => $record->user_id])),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('approval')
                    ->label('Approval')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        AudioApproval::Pending => 'warning',
                        AudioApproval::Approved => 'success',
                        AudioApproval::Rejected => 'danger',
                    })
                    ->icon(fn($state) => match ($state) {
                        AudioApproval::Pending => Heroicon::OutlinedClock,
                        AudioApproval::Approved => Heroicon::OutlinedCheckCircle,
                        AudioApproval::Rejected => Heroicon::OutlinedXCircle,
                    }),
                TextColumn::make('original_path')
                    ->label('Listen')
                    ->formatStateUsing(fn($state) => $state ? new HtmlString('<audio controls class="w-40 h-8" preload="none"><source src="' . Storage::url($state) . '" type="audio/mpeg"></audio>') : 'No Audio')
                    ->alignCenter(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('approve')
                        ->label('Approve')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('success')
                        ->action(fn($record) => $record->update(['approval' => AudioApproval::Approved]))
                        ->visible(fn($record) => $record->approval !== AudioApproval::Approved),
                    Action::make('reject')
                        ->label('Reject')
                        ->icon(Heroicon::OutlinedXMark)
                        ->color('danger')
                        ->action(fn($record) => $record->update(['approval' => AudioApproval::Rejected]))
                        ->visible(fn($record) => $record->approval !== AudioApproval::Rejected),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
