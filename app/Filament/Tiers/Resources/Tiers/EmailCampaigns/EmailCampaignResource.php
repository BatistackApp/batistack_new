<?php

namespace App\Filament\Tiers\Resources\Tiers\EmailCampaigns;

use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages\CreateEmailCampaign;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages\EditEmailCampaign;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Pages\ListEmailCampaigns;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Schemas\EmailCampaignForm;
use App\Filament\Tiers\Resources\Tiers\EmailCampaigns\Tables\EmailCampaignsTable;
use App\Models\Tiers\EmailCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
            \App\Filament\Tiers\Resources\Tiers\EmailCampaigns\RelationManagers\RecipientsRelationManager::class,
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
