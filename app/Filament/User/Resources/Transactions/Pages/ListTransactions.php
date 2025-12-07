<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Transactions\Pages;

use App\Filament\User\Resources\Transactions\TransactionResource;
use Filament\Resources\Pages\ListRecords;

final class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;
}
