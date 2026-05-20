<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Calls\Schemas;

use App\Filament\Admin\Resources\Campaigns\CampaignResource;
use App\Filament\Admin\Resources\Contacts\ContactResource;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Models\Call;
use Filament\Infolists\Components\TextEntry;
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
                                        ->formatStateUsing(fn ($state) => secondsToHuman($state))
                                        ->label('Duration'),
                                    TextEntry::make('retries')
                                        ->icon(Tabler::Repeat)
                                        ->label('Retries')
                                        ->suffix(' times'),
                                ]),
                            ]),

                        Section::make('Timestamps')
                            ->schema([
                                Group::make()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('scheduled_at')
                                            ->icon(Tabler::Stopwatch)
                                            ->iconColor('gray')
                                            ->label('Scheduled At')
                                            ->dateTime()
                                            ->placeholder('Not Scheduled'),
                                        TextEntry::make('created_at')
                                            ->icon(Tabler::CalendarPlus)
                                            ->label('Created At')
                                            ->dateTime(),
                                        TextEntry::make('updated_at')
                                            ->icon(Tabler::CalendarUp)
                                            ->label('Updated At')
                                            ->dateTime(),
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
                                            ->url(fn (Call $record) => $record->campaign ? CampaignResource::getUrl('view', ['record' => $record->campaign_id]) : null),
                                        TextEntry::make('contact.nameOrNumber')
                                            ->icon(Tabler::AddressBook)
                                            ->label('Contact')
                                            ->placeholder('Not Assigned')
                                            ->tooltip(fn (Call $record) => $record->phone_number ?? '-')
                                            ->url(fn (Call $record) => $record->contact ? ContactResource::getUrl('view', ['record' => $record->contact_id]) : null),
                                        TextEntry::make('caller.name')
                                            ->label('Caller')
                                            ->placeholder('Not Assigned'),
                                        TextEntry::make('audio.title')
                                            ->label('Audio')
                                            ->placeholder('Not Assigned'),
                                        TextEntry::make('user.name')
                                            ->label('User')
                                            ->icon(Tabler::User)
                                            ->placeholder('No user found')
                                            ->url(fn ($record) => $record->user_id ? CustomerResource::getUrl('view', ['record' => $record->user_id]) : null),
                                    ]),
                            ]),
                    ]),

            ]);
    }
}
