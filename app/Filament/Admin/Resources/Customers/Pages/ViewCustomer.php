<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Customers\Pages;

use App\Filament\Admin\Resources\Customers\Actions\AddBalanceAction;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Customers\Schemas\CustomerInfolist;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use STS\FilamentImpersonate\Actions\Impersonate;

final class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Impersonate::make()
                ->label('Login as Customer')
                ->requiresConfirmation()
                ->color('warning'),
            AddBalanceAction::make(),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load relationship counts for statistics section
        $record = $this->getRecord();

        $data['campaigns_count'] = $record->campaigns()->count();
        $data['calls_count'] = $record->calls()->count();
        $data['audio_count'] = $record->audio()->count();
        $data['phonebooks_count'] = $record->phonebooks()->count();
        $data['templates_count'] = $record->templates()->count();
        $data['transactions_count'] = $record->transactions()->count();

        return $data;
    }
}
