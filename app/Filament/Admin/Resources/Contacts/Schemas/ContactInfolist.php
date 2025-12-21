<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Contacts\Schemas;

use App\Filament\Admin\Resources\Phonebooks\PhonebookResource;
use App\Models\Contact;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use LaraZeus\Tabler\Tabler;

final class ContactInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Contact Overview Section
                Section::make('Contact Overview')
                    ->description('Essential contact information')
                    ->icon(Tabler::UserCircle)
                    ->schema([
                        TextEntry::make('nameOrNumber')
                            ->label('Full Name')
                            ->icon(Tabler::User)
                            ->placeholder('No name provided'),
                        TextEntry::make('phone_number')
                            ->label('Phone Number')
                            ->icon(Tabler::Phone)
                            ->copyable()
                            ->copyMessage('Phone number copied!')
                            ->copyMessageDuration(1500)
                            ->url(fn (Contact $record) => 'tel:'.$record->phone_number),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Personal Information Section
                Section::make('Personal Information')
                    ->description('Name details')
                    ->icon(Tabler::IdBadge)
                    ->schema([
                        TextEntry::make('first_name')
                            ->label('First Name')
                            ->icon(Tabler::User)
                            ->placeholder('Not provided'),
                        TextEntry::make('last_name')
                            ->label('Last Name')
                            ->icon(Tabler::User)
                            ->placeholder('Not provided'),
                    ])
                    ->columns()
                    ->collapsible(),

                // Phonebook Association Section
                Section::make('Phonebook Association')
                    ->description('Which phonebook this contact belongs to')
                    ->icon(Tabler::AddressBook)
                    ->schema([
                        TextEntry::make('phonebook.name')
                            ->label('Phonebook')
                            ->icon(Tabler::Book)
                            ->badge()
                            ->color('info')
                            ->url(fn (Contact $record) => $record->phonebook_id ? PhonebookResource::getUrl('view', ['record' => $record->phonebook_id]) : null),
                        TextEntry::make('phonebook.user.name')
                            ->label('Owner')
                            ->icon(Tabler::UserCheck)
                            ->placeholder('No owner'),
                    ])
                    ->columns()
                    ->collapsible(),

                // System Information Section
                Section::make('System Information')
                    ->description('Record timestamps and metadata')
                    ->icon(Tabler::InfoCircle)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->icon(Tabler::CalendarPlus)
                            ->since()
                            ->tooltip(fn (Contact $record) => $record->created_at ? $record->created_at->format('M j, Y \a\t h:i A') : 'Unknown')
                            ->color('gray'),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->icon(Tabler::Refresh)
                            ->since()
                            ->tooltip(fn (Contact $record) => $record->updated_at ? $record->updated_at->format('M j, Y \a\t h:i A') : 'Never updated')
                            ->color('gray'),
                    ])
                    ->columns()
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}
