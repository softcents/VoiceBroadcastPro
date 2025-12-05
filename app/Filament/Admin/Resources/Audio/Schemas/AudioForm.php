<?php

namespace App\Filament\Admin\Resources\Audio\Schemas;

use App\Enums\AudioApproval;
use App\Enums\AudioArtist;
use App\Enums\AudioGender;
use App\Enums\AudioLanguage;
use App\Enums\AudioType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AudioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->label('User')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Select::make('type')
                                    ->label('Type')
                                    ->options(AudioType::class)
                                    ->required()
                                    ->live(), // Reactive
                                Select::make('approval')
                                    ->label('Approval Status')
                                    ->options(AudioApproval::class)
                                    ->default(AudioApproval::Pending)
                                    ->required(),
                            ]),

                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),

                        // TTS Fields
                        Grid::make(3)
                            ->schema([
                                Select::make('language')
                                    ->label('Language')
                                    ->options(AudioLanguage::class),
                                Select::make('gender')
                                    ->label('Gender')
                                    ->options(AudioGender::class),
                                Select::make('artist')
                                    ->label('Artist')
                                    ->options(AudioArtist::class),
                            ])
                            ->visible(fn (Get $get) => $get('type') === AudioType::TTS->value),

                        Textarea::make('message')
                            ->label('Script / Message')
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('type') === AudioType::TTS->value),

                        // Upload Field (Visible if Record or just always visible as backup?)
                        // If TTS, system generates file. If Record, user uploads.
                        FileUpload::make('original_path')
                            ->label('Audio File')
                            ->disk('public')
                            ->directory('audio/original')
                            ->visibility('public')
                            ->acceptedFileTypes(['audio/*'])
                            ->visible(fn (Get $get) => $get('type') === AudioType::Record->value)
                            ->columnSpanFull(),

                        // System generated fields
                        Grid::make(2)
                            ->schema([
                                TextInput::make('duration')
                                    ->label('Duration (sec)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('size')
                                    ->label('Size (bytes)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->disabled(),
                    ])
            ]);
    }
}
