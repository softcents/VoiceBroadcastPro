<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calls\Schemas;

use App\Enums\AudioApproval;
use App\Enums\AudioConversionStatus;
use App\Models\Caller;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use LaraZeus\Tabler\Tabler;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

final class CallForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(components: [
                Section::make()
                    ->schema(components: [
                        Select::make('caller_id')
                            ->label('Caller ID')
                            ->relationship('caller', modifyQueryUsing: function ($query) {
                                return $query->enabled();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn (Caller $caller) => $caller->name)
                        /*->getSearchResultsUsing(function ($search) {
                            return auth()
                                ->user()
                                ->callers()
                                ->enabled()
                                ->where('caller_name', 'like', "%{$search}%")
                                ->orWhere('caller_number', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('id', 'caller_number')
                                ->toArray();
                        })*/,
                        PhoneInput::make('phone_number')
                            ->label('Phone Number')
                            ->onlyCountries(['BD'])
                            ->defaultCountry('BD')
                            ->required()
                            ->rules(['phone:BD']),
                        Select::make('audio_id')
                            ->relationship(
                                name: 'audio',
                                titleAttribute: 'title',
                                modifyQueryUsing: function ($query) {
                                    return $query->where('approval', AudioApproval::Approved)
                                        ->where('conversion_status', AudioConversionStatus::Completed);
                                })
                            ->label('Audio')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->prefixIcon(Tabler::Music),
                        DateTimePicker::make('scheduled_at')
                            ->label('Scheduled At')
                            ->prefixIcon(Tabler::CalendarTime)
                            ->minDate(now()->addMinutes(5))
                            ->maxDate(now()->addDays(30)),
                    ]),
            ]);
    }
}
