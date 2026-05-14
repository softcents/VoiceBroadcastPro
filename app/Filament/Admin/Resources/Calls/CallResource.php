<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Calls;

use App\Filament\Admin\Resources\Calls\Pages\ListCalls;
use App\Filament\Admin\Resources\Calls\Pages\ViewCall;
use App\Filament\Admin\Resources\Calls\RelationManagers\TransactionsRelationManager;
use App\Filament\Admin\Resources\Calls\Schemas\CallForm;
use App\Filament\Admin\Resources\Calls\Schemas\CallInfolist;
use App\Filament\Admin\Resources\Calls\Tables\CallsTable;
use App\Models\Call;
use App\Models\Scopes\OwnedByAuthUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraZeus\Tabler\Tabler;

final class CallResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static ?string $recordTitleAttribute = 'phone_number';

    protected static string|BackedEnum|null $navigationIcon = Tabler::PhoneCall;

    protected static ?int $navigationSort = 13;

    public static function form(Schema $schema): Schema
    {
        return CallForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CallInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCalls::route('/'),
            'view' => ViewCall::route('/{record}'),
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
