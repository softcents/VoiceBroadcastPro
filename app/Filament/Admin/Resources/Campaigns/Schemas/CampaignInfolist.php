<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Campaigns\Schemas;

use App\Enums\CampaignSource;
use App\Enums\CampaignStatus;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Phonebooks\PhonebookResource;
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
                                            ->url(fn (Campaign $record) => CustomerResource::getUrl('view', ['record' => $record->user_id])),

                                        TextEntry::make('audio.title')
                                            ->label('Audio File')
                                            ->icon(Tabler::Music)
                                            ->limit(20),

                                        TextEntry::make('phonebook.name')
                                            ->label('Phonebook')
                                            ->icon(Tabler::AddressBook)
                                            ->placeholder('N/A')
                                            ->url(fn (Campaign $record) => PhonebookResource::getUrl('view', ['record' => $record->phonebook_id])),
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
                                    ->formatStateUsing(fn ($state) => $state->name)
                                    ->icon(fn (Campaign $record) => match ($record->status) {
                                        CampaignStatus::Pending,
                                        CampaignStatus::Cancelled => Tabler::Clock,
                                        CampaignStatus::Processing => Tabler::Refresh,
                                        CampaignStatus::Completed => Tabler::Check,
                                        CampaignStatus::Failed => Tabler::X,
                                    })
                                    ->color(fn (Campaign $record) => match ($record->status) {
                                        CampaignStatus::Pending,
                                        CampaignStatus::Cancelled => 'warning',
                                        CampaignStatus::Processing => 'primary',
                                        CampaignStatus::Completed => 'success',
                                        CampaignStatus::Failed => 'danger',
                                    }),

                                TextEntry::make('source')
                                    ->label('Source')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state->name)
                                    ->color(fn ($state) => match ($state) {
                                        CampaignSource::Phonebook => 'success',
                                        CampaignSource::Manual => 'primary',
                                        CampaignSource::Import => 'secondary',
                                    })
                                    ->icon(fn ($state) => match ($state) {
                                        CampaignSource::Phonebook => Tabler::AddressBook,
                                        CampaignSource::Manual => Tabler::Writing,
                                        CampaignSource::Import => Tabler::FileImport,
                                    }),

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
