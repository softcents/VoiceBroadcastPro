<?php

namespace App\Filament\Admin\Resources\Calls;

use App\Filament\Admin\Resources\Calls\Pages\CreateCall;
use App\Filament\Admin\Resources\Calls\Pages\ListCalls;
use App\Filament\Admin\Resources\Calls\Schemas\CallForm;
use App\Filament\Admin\Resources\Calls\Tables\CallsTable;
use App\Models\Call;
use App\Models\Scopes\OwnedByAuthUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraZeus\Tabler\Tabler;

class CallResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static ?string $recordTitleAttribute = 'phone_number';
    protected static string | BackedEnum | null $navigationIcon = Tabler::PhoneCall;

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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                OwnedByAuthUser::class,
            ]);
    }
}
