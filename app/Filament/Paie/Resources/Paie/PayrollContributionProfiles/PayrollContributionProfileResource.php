<?php

namespace App\Filament\Paie\Resources\Paie\PayrollContributionProfiles;

use App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\Pages\CreatePayrollContributionProfile;
use App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\Pages\EditPayrollContributionProfile;
use App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\Pages\ListPayrollContributionProfiles;
use App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\RelationManagers\RatesRelationManager;
use App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\Schemas\PayrollContributionProfileForm;
use App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\Tables\PayrollContributionProfilesTable;
use App\Models\Paie\PayrollContributionProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayrollContributionProfileResource extends Resource
{
    protected static ?string $model = PayrollContributionProfile::class;

    protected static ?string $modelLabel = 'Profil de Cotisation';

    protected static ?string $pluralModelLabel = 'Profils de Cotisations';

    protected static string|null|\UnitEnum $navigationGroup = 'Paramètres';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PayrollContributionProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollContributionProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollContributionProfiles::route('/'),
            'create' => CreatePayrollContributionProfile::route('/create'),
            'edit' => EditPayrollContributionProfile::route('/{record}/edit'),
        ];
    }
}
