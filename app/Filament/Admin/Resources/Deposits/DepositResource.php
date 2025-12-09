<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Deposits;

use App\Filament\Admin\Resources\Deposits\Pages\CreateDeposit;
use App\Filament\Admin\Resources\Deposits\Pages\EditDeposit;
use App\Filament\Admin\Resources\Deposits\Pages\ListDeposits;
use App\Filament\Admin\Resources\Deposits\Schemas\DepositForm;
use App\Filament\Admin\Resources\Deposits\Schemas\DepositInfolist;
use App\Filament\Admin\Resources\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

final class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|UnitEnum|null $navigationGroup = 'Financial';

    protected static ?int $navigationSort = 61;

    protected static ?string $recordTitleAttribute = 'transaction_id';

    public static function form(Schema $schema): Schema
    {
        return DepositForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepositsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DepositInfolist::configure($schema);
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
            'index' => ListDeposits::route('/'),
            'create' => CreateDeposit::route('/create'),
            'edit' => EditDeposit::route('/{record}/edit'),
        ];
    }
}
