<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Settings\CallingSetting;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

final class CallingSettings extends SettingsPage
{
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string $settings = CallingSetting::class;

    protected static ?string $title = 'Calling';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('pulse_rate')
                            ->label('Pulse Rate')
                            ->prefix('BDT')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        TextInput::make('pulse_duration')
                            ->label('Pulse Duration')
                            ->suffix('seconds')
                            ->numeric()
                            ->minValue(1)
                            ->step(1)
                            ->required(),
                        TextInput::make('max_retry_attempts')
                            ->label('Max Retry Attempts')
                            ->suffix('times')
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->required(),
                    ]),
            ]);
    }
}
