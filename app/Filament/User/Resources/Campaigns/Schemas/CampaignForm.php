<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns\Schemas;

use App\Enums\AudioApproval;
use App\Enums\CampaignSource;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use LaraZeus\Tabler\Tabler;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

final class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Campaign Details')
                            ->icon(Tabler::Ad2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Campaign Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull()
                                    ->prefixIcon(Tabler::AlphabetLatin),

                                Textarea::make('description')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Grid::make(1)
                                    ->schema([
                                        Select::make('audio_id')
                                            ->label('Audio File')
                                            ->relationship('audio', 'title', modifyQueryUsing: fn ($query) => $query->where('approval', AudioApproval::Approved))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->prefixIcon(Tabler::Music),
                                    ]),

                                Select::make('phonebook_id')
                                    ->label('Contact List')
                                    ->relationship('phonebook', 'name', modifyQueryUsing: function ($query) {
                                        return $query->whereHas('contacts');
                                    })
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        return $record->name.' ('.$record->contacts()->count().' contacts)';
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon(Tabler::AddressBook)
                                    ->visible(fn ($get) => $get('source') === CampaignSource::Phonebook)
                                    ->required(fn ($get) => $get('source') === CampaignSource::Phonebook),

                                FileUpload::make('file_path')
                                    ->label('Contact List (CSV)')
                                    ->disk('local') // Adjust disk as needed
                                    ->directory('campaign-files')
                                    ->acceptedFileTypes(['text/csv', 'text/plain', '.csv'])
                                    ->visible(fn ($get) => $get('source') === CampaignSource::Import)
                                    ->required(fn ($get) => $get('source') === CampaignSource::Import)
                                    ->hintAction(Action::make('sample_file')
                                        ->label('Download Sample File')
                                        ->url(url('samples/contacts.csv'))
                                        ->openUrlInNewTab()
                                        ->icon(Tabler::Download)),

                                Repeater::make('manual_numbers')
                                    ->label('Manual Numbers')
                                    ->addActionLabel('Add Number')
                                    ->schema([
                                        PhoneInput::make('number')
                                            ->label('Phone Number')
                                            ->defaultCountry('BD')
                                            ->required()
                                            ->rules(['phone:BD']),
                                    ])
                                    ->columnSpanFull()
                                    ->minItems(1)
                                    ->grid(2)
                                    ->compact()
                                    ->visible(fn ($get) => $get('source') === CampaignSource::Manual)
                                    ->required(fn ($get) => $get('source') === CampaignSource::Manual),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Settings')
                            ->icon(Tabler::Settings)
                            ->schema([
                                Select::make('caller_id')
                                    ->label('Caller ID')
                                    ->relationship(
                                        name: 'caller',
                                        titleAttribute: 'caller_number',
                                        modifyQueryUsing: function (Builder $query): Builder {
                                            return $query->whereHas('users', function (Builder $q) {
                                                $q->where('id', Auth::id());
                                            });
                                        }
                                    )
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        return $record->name;
                                    })
                                    ->preload()
                                    ->searchable(['caller_name', 'caller_number'])
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->required(),
                                Select::make('source')
                                    ->label('Source')
                                    ->options(CampaignSource::class)
                                    ->required()
                                    ->live()
                                    ->prefixIcon(Tabler::Link)
                                    ->searchable()
                                    ->selectablePlaceholder(false)
                                    ->default(CampaignSource::Phonebook),

                                DateTimePicker::make('scheduled_at')
                                    ->label('Launch Date')
                                    ->helperText('Leave empty to launch immediately')
                                    ->prefixIcon(Tabler::Calendar)
                                    ->native(false)
                                    ->minDate(now()->addMinutes(5))
                                    ->maxDate(now()->addMonth()),
                            ]),
                    ]),
            ]);
    }
}
