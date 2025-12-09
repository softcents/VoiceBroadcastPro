<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ApiTokens;

use App\Filament\User\Resources\ApiTokens\Pages\ListApiTokens;
use App\Filament\User\Resources\ApiTokens\Tables\ApiTokensTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;
use UnitEnum;

final class ApiTokensResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static string|UnitEnum|null $navigationGroup = 'Developers';

    protected static ?string $modelLabel = 'API Token';

    protected static ?string $navigationLabel = 'API Tokens';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return ApiTokensTable::configure($table);
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
            'index' => ListApiTokens::route('/'),
        ];
    }
}
