<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssetMaintenanceForm
{
    public static function configure(Schema $schema, bool $isRelationManager = false): Schema
    {
        $components = [];

        if (! $isRelationManager) {
            $components[] = \Filament\Forms\Components\Select::make('fixed_asset_id')
                ->label('Actif / Machine')
                ->relationship('fixedAsset', 'name')
                ->searchable()
                ->preload()
                ->required();
        }

        $components = array_merge($components, [
            \Filament\Forms\Components\Select::make('chantier_id')
                    ->label('Chantier imputé (Optionnel)')
                    ->relationship('chantier', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Si la panne a eu lieu sur un chantier, le coût de réparation y sera imputé.'),
                \Filament\Forms\Components\DatePicker::make('maintenance_date')
                    ->label('Date d\'intervention')
                    ->required()
                    ->default(now()),
                \Filament\Forms\Components\Select::make('type')
                    ->label('Type d\'intervention')
                    ->options([
                        'preventive' => 'Entretien Préventif',
                        'curative' => 'Réparation Curative',
                        'control' => 'Contrôle Réglementaire (VGP)',
                    ])
                    ->required(),
                \Filament\Forms\Components\TextInput::make('cost_ht')
                    ->label('Coût HT')
                    ->numeric()
                    ->prefix('€')
                    ->default(0),
                \Filament\Forms\Components\TextInput::make('provider_name')
                    ->label('Garage / Prestataire'),
                \Filament\Forms\Components\TextInput::make('invoice_ref')
                    ->label('Référence Facture'),
                \Filament\Forms\Components\Textarea::make('description')
                    ->label('Description de la panne / intervention')
                    ->columnSpanFull()
                    ->required(),
        ]);

        return $schema->components($components);
    }
}
