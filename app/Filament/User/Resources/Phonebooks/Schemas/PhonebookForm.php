<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Phonebooks\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PhonebookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
