<?php

namespace App\Filament\Commerce\Resources\SubcontractorSituations\Schemas;

use Filament\Schemas\Schema;

class SubcontractorSituationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('subcontractor_id')
                    ->label('Sous-traitant')
                    ->relationship('subcontractor', 'name')
                    ->required()
                    ->searchable(),
                \Filament\Forms\Components\Select::make('chantier_id')->label('Chantier')
                    ->label('Chantier')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\Select::make('purchase_order_id')
                    ->label('Commande Fournisseur')
                    ->relationship('order', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('reference')->label('Référence')
                    ->label('Référence')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('progress_percentage')
                    ->label('Avancement (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                \Filament\Forms\Components\TextInput::make('total_ht')
                    ->label('Total HT')
                    ->numeric()
                    ->prefix('€'),
                \Filament\Forms\Components\TextInput::make('retenue_garantie_amount')
                    ->label('Retenue de garantie')
                    ->numeric()
                    ->prefix('€'),
                \Filament\Forms\Components\Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(\App\Enums\Commerce\InvoiceStatus::class)
                    ->required()
                    ->default(\App\Enums\Commerce\InvoiceStatus::DRAFT),
            ]);
    }
}
