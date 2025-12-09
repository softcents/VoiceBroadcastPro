<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calls;

use App\Filament\User\Resources\Calls\Pages\CreateCall;
use App\Filament\User\Resources\Calls\Pages\ListCalls;
use App\Filament\User\Resources\Calls\Schemas\CallForm;
use App\Filament\User\Resources\Calls\Tables\CallsTable;
use App\Models\Call;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\Tabler\Tabler;

final class CallResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static ?string $recordTitleAttribute = 'phone_number';

    protected static string|BackedEnum|null $navigationIcon = Tabler::PhoneCall;

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return CallForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalls::route('/'),
            'create' => CreateCall::route('/create'),
        ];
    }
}
