<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\Campaigns;

use App\Filament\User\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\User\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\User\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\User\Resources\Campaigns\Schemas\CampaignForm;
use App\Filament\User\Resources\Campaigns\Schemas\CampaignInfolist;
use App\Filament\User\Resources\Campaigns\Tables\CampaignsTable;
use App\Models\Campaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraZeus\Tabler\Tabler;

final class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Tabler::Speakerphone;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CampaignForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CampaignInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CallsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'view' => Pages\ViewCampaign::route('/{record}'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
