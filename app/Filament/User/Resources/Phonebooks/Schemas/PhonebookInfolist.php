<?php

namespace App\Filament\User\Resources\Phonebooks\Schemas;

use App\Filament\User\Resources\Customers\CustomerResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use LaraZeus\Tabler\Tabler;

class PhonebookInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns()
                    ->schema([
                        TextEntry::make('name')
                            ->icon(Tabler::H1)
                            ->label('Name'),
                        TextEntry::make('created_at')
                            ->icon(Tabler::ClockPlus)
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->icon(Tabler::ClockEdit)
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->icon(Tabler::TextCaption)
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
