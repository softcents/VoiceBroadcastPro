<?php

namespace App\Filament\Admin\Resources\Calls;

use App\Filament\Admin\Resources\Calls\Pages\CreateCall;
use App\Filament\Admin\Resources\Calls\Pages\EditCall;
use App\Filament\Admin\Resources\Calls\Pages\ListCalls;
use App\Filament\Admin\Resources\Calls\Schemas\CallForm;
use App\Filament\Admin\Resources\Calls\Tables\CallsTable;
use App\Models\Call;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use LaraZeus\Tabler\Tabler;

class CallResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static string|BackedEnum|null $navigationIcon = Tabler::Phone;

    protected static ?string $recordTitleAttribute = 'phone_number';

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
