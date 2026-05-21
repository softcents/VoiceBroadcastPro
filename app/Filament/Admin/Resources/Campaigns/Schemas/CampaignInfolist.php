<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Campaigns\Schemas;

use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Groups\GroupResource;
use App\Filament\Infolists\Components\AudioPlayerEntry;
use App\Models\Campaign;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use LaraZeus\Tabler\Tabler;

final class CampaignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make()
                            ->heading('Campaign Details')
                            ->description('Detailed information about the campaign')
                            ->icon(Tabler::Ad2)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Campaign Title')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextSize::Large)
                                    ->columnSpanFull(),

                                TextEntry::make('description')
                                    ->markdown()
                                    ->placeholder('No description provided.')
                                    ->columnSpanFull(),

                                Grid::make([
                                    'default' => 1,
                                    'sm' => 2,
                                    'md' => 3,
                                ])
                                    ->schema([
                                        TextEntry::make('user.name')
                                            ->label('Customer')
                                            ->icon(Tabler::User)
                                            ->url(fn (Campaign $record) => $record->user_id ? CustomerResource::getUrl('view', ['record' => $record->user_id]) : null),

                                        TextEntry::make('audio.title')
                                            ->label('Audio File')
                                            ->icon(Tabler::Music)
                                            ->limit(20),

                                        TextEntry::make('group.name')
                                            ->label('Group')
                                            ->icon(Tabler::AddressBook)
                                            ->placeholder('N/A')
                                            ->url(fn (Campaign $record) => $record->group_id ? GroupResource::getUrl('view', ['record' => $record->group_id]) : null),
                                    ]),
                            ]),

                        Section::make()
                            ->heading('Audio Preview')
                            ->icon(Tabler::PlayerPlay)
                            ->schema([
                                AudioPlayerEntry::make('audio.original_path')
                                    ->label('Player')
                                    ->hiddenLabel(),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make()
                            ->icon(Tabler::InfoCircle)
                            ->heading('Status & Source')
                            ->description('Current status and source of the campaign')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Current Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state->name),

                                TextEntry::make('scheduled_at')
                                    ->label('Launch Date')
                                    ->dateTime()
                                    ->icon(Tabler::CalendarEvent),
                            ]),

                        Section::make()
                            ->heading('Timestamps')
                            ->icon(Tabler::Clock)
                            ->collapsed()
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime()
                                    ->size(TextSize::Small),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime()
                                    ->size(TextSize::Small),
                            ]),
                    ]),
            ]);
    }
}
