<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Deposits\Pages;

use App\Filament\Admin\Resources\Deposits\DepositResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditDeposit extends EditRecord
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
