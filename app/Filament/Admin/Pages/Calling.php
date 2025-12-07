<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Settings\CallingSetting;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

final class Calling extends SettingsPage
{
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string $settings = CallingSetting::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('rate_per_minute')
                            ->numeric()
                            ->required(),
                    ]),
            ]);
    }
}
