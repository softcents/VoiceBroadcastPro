<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Phonebooks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use LaraZeus\Tabler\Tabler;

final class PhonebookInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextEntry::make('name')
                            ->icon(Tabler::H1)
                            ->label('Name'),
                        TextEntry::make('description')
                            ->icon(Tabler::TextCaption)
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make()
                    ->columns()
                    ->schema([
                        TextEntry::make('contacts_count')
                            ->counts('contacts')
                            ->icon(Tabler::AddressBook)
                            ->label('Contacts')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->icon(Tabler::ClockPlus)
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->icon(Tabler::ClockEdit)
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
