<?php

namespace App\Filament\Commerce\Resources\SubcontractorSituations\Schemas;

use App\Enums\Commerce\InvoiceStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubcontractorSituationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subcontractor_id')
                    ->label('Sous-traitant')
                    ->relationship('subcontractor', 'name')
                    ->required()
                    ->searchable(),
                Select::make('chantier_id')->label('Chantier')
                    ->label('Chantier')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                Select::make('purchase_order_id')
                    ->label('Commande Fournisseur')
                    ->relationship('order', 'reference')
                    ->searchable(),
                TextInput::make('reference')->label('Référence')
                    ->label('Référence')
                    ->required()
                    ->maxLength(255),
                TextInput::make('progress_percentage')
                    ->label('Avancement (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                TextInput::make('total_ht')
                    ->label('Total HT')
                    ->numeric()
                    ->prefix('€'),
                TextInput::make('retenue_garantie_amount')
                    ->label('Retenue de garantie')
                    ->numeric()
                    ->prefix('€'),
                Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(InvoiceStatus::class)
                    ->required()
                    ->default(InvoiceStatus::DRAFT),
            ]);
    }
}
