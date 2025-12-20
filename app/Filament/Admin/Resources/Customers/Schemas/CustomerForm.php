<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Customers\Schemas;

use App\Settings\CallingSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use LaraZeus\Tabler\Tabler;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

final class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->prefixIcon(Tabler::User)
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->prefixIcon(Tabler::Mail)
                            ->required()
                            ->maxLength(255),
                        PhoneInput::make('phone')
                            ->label('Phone')
                            ->required()
                            ->onlyCountries(['BD'])
                            ->defaultCountry('BD')
                            ->rules(['phone:BD']),
                        TextInput::make('pulse_rate')
                            ->label('Pulse Rate')
                            ->prefix('BDT')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->default(app(CallingSetting::class)->pulse_rate)
                            ->required(),
                        TextInput::make('pulse_duration')
                            ->label('Pulse Duration')
                            ->suffix('seconds')
                            ->numeric()
                            ->minValue(1)
                            ->step(1)
                            ->default(app(CallingSetting::class)->pulse_duration)
                            ->required(),
                        TextInput::make('password')
                            ->label('Password')
                            ->hint('Leave empty to keep the current password.')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->revealable()
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => filled($state))
                            ->prefixAction(Action::make('generate_password')
                                ->label('Generate')
                                ->iconButton()
                                ->icon(Heroicon::ArrowPath)
                                ->action(function (TextInput $component) {
                                    $component->state(bin2hex(random_bytes(4)));
                                })
                            ),
                    ]),
            ]);
    }
}
