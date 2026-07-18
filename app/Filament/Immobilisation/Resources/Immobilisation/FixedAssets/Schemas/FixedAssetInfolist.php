<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;

class FixedAssetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de l\'actif')
                    ->schema([
                        TextEntry::make('name')->label('Nom'),
                        TextEntry::make('category.name')->label('Catégorie'),
                        TextEntry::make('purchase_price')->label('Valeur d\'achat')->money('EUR'),
                        TextEntry::make('status')->badge(),
                    ])->columns(2),

                Section::make('Tableau d\'amortissement prévisionnel')
                    ->schema([
                        ViewEntry::make('depreciations')
                            ->view('filament.immobilisation.infolists.components.depreciations-table')
                    ])
            ]);
    }
}
