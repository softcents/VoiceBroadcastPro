<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use App\Enums\UserType;
use App\Models\TTSArtist;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Details')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->url(fn(User $record) => 'mailto:' . $record->email),
                        TextEntry::make('phone')
                            ->label('Phone')
                            ->url(fn(User $record) => 'tel:' . $record->phone),
                        TextEntry::make('balance')
                            ->label('Balance')
                            ->money('BDT'), // Assuming USD for now, based on balance attribute in User model
                        TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime()
                            ->placeholder('Not Verified'),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }
}
