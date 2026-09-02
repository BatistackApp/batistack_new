<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns;

use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages\CreateEmailCampaign;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages\EditEmailCampaign;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages\ListEmailCampaigns;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\RelationManagers\RecipientsRelationManager;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Schemas\EmailCampaignForm;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Tables\EmailCampaignsTable;
use App\Models\Tiers\EmailCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Mailbox;

    protected static ?string $modelLabel = 'Campagnes Emailing';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EmailCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailCampaignsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailCampaigns::route('/'),
            'create' => CreateEmailCampaign::route('/create'),
            'edit' => EditEmailCampaign::route('/{record}/edit'),
        ];
    }
}
