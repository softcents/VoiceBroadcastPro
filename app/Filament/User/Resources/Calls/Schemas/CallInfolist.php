<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Calls\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class CallInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextEntry::make('id'),
            ]);
    }
}
