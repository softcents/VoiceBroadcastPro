<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calls\Schemas;

use App\Filament\User\Resources\Campaigns\CampaignResource;
use App\Filament\User\Resources\Contacts\ContactResource;
use App\Models\Call;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use LaraZeus\Tabler\Tabler;

final class CallInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Group::make()
                    ->poll('5s')
                    ->schema([
                        Grid::make(['md' => 2])
                            ->schema([
                                Section::make([
                                    TextEntry::make('phone_number')
                                        ->label('Phone Number')
                                        ->weight(FontWeight::Bold)
                                        ->size('lg'),
                                    TextEntry::make('type')
                                        ->badge(),
                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge(),
                                ]),
                                Section::make([
                                    TextEntry::make('cost')
                                        ->money('BDT')
                                        ->label('Cost')
                                        ->weight(FontWeight::Bold)
                                        ->size('lg'),
                                    TextEntry::make('duration')
                                        ->icon('heroicon-m-clock')
                                        ->formatStateUsing(fn($state) => secondsToHuman($state))
                                        ->label('Duration'),
                                ]),
                            ]),

                        Section::make('Timestamps')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('scheduled_at')
                                            ->dateTime()
                                            ->placeholder('Not Scheduled'),
                                        TextEntry::make('called_at')
                                            ->dateTime()
                                            ->placeholder('-')
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('answered_at')
                                            ->dateTime()
                                            ->placeholder('-'),
                                        TextEntry::make('ended_at')
                                            ->dateTime()
                                            ->placeholder('-'),
                                    ]),
                            ]),

                        Section::make('Relations')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('campaign.title')
                                            ->icon(Tabler::Speakerphone)
                                            ->label('Campaign')
                                            ->placeholder('Not Assigned')
                                            ->url(fn(Call $record) => $record->campaign ? CampaignResource::getUrl('view', ['record' => $record->campaign_id]) : null),
                                        TextEntry::make('contact.nameOrNumber')
                                            ->icon(Tabler::AddressBook)
                                            ->label('Contact')
                                            ->placeholder('Not Assigned')
                                            ->tooltip(fn(Call $record) => $record->phone_number ?? '-')
                                            ->url(fn(Call $record) => $record->contact ? ContactResource::getUrl('view', ['record' => $record->contact_id]) : null),
                                        TextEntry::make('caller.name')
                                            ->label('Caller')
                                            ->placeholder('Not Assigned'),
                                        TextEntry::make('audio.title')
                                            ->label('Audio')
                                            ->placeholder('Not Assigned'),
                                    ]),
                            ]),
                    ])

            ]);
    }
}
