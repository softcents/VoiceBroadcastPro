<?php

namespace App\Filament\Admin\Pages;

use App\Enums\TTSEngine;
use App\Settings\TTSSetting;
use Filament\Forms\Components\Select;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class TTSSettings extends SettingsPage
{
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string $settings = TTSSetting::class;

    protected static ?string $title = 'TTS';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('engine')
                            ->label('TTS Engine')
                            ->options(TTSEngine::class)
                            ->default(TTSEngine::Azure)
                            ->searchable()
                            ->selectablePlaceholder(false)
                            ->required(),
                    ])
            ]);
    }
}
