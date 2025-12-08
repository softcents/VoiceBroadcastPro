<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Callers\Schemas;

use App\Enums\UserType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

final class CallerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('server_id')
                            ->relationship('server', 'name')
                            ->label('Server')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('caller_name')
                            ->label('Caller Name')
                            ->required(),
                        TextInput::make('caller_number')
                            ->label('Caller Number')
                            ->required(),
                        Select::make('users')
                            ->relationship('users', 'name', modifyQueryUsing: function (Builder $query) {
                                return $query->where('type', UserType::User);
                            })
                            ->label('Assigned Users')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        Toggle::make('enabled')
                            ->label('Enabled')
                            ->required(),
                    ]),
            ]);
    }
}
