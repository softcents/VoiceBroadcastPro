<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Calls\Schemas;

use App\Enums\CallStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use LaraZeus\Tabler\Tabler;

final class CallForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone_number')
                    ->label('Phone Number')
                    ->tel()
                    ->required()
                    ->prefixIcon(Tabler::Phone),
                Select::make('status')
                    ->label('Status')
                    ->options(CallStatus::class)
                    ->required()
                    ->prefixIcon(Tabler::InfoCircle),
                Select::make('campaign_id')
                    ->relationship('campaign', 'title')
                    ->label('Campaign')
                    ->searchable()
                    ->preload()
                    ->prefixIcon(Tabler::Ad2),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Customer')
                    ->searchable()
                    ->preload()
                    ->prefixIcon(Tabler::User),
                Textarea::make('content')
                    ->label('Notes/Content')
                    ->columnSpanFull(),
            ]);
    }
}
