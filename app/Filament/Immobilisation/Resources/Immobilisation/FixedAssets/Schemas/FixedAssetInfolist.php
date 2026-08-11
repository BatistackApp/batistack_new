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
                        TextEntry::make('status')->label('Statut')
                            ->label('Statut')
                            ->badge(),
                        TextEntry::make('vgp_status')
                            ->label('Statut VGP')
                            ->badge()
                            ->colors([
                                'success' => 'ok',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                'gray' => 'none',
                            ])
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'ok' => 'À jour',
                                'warning' => 'Bientôt',
                                'danger' => 'Expirée',
                                'none' => 'Non soumis',
                                default => $state,
                            }),
                        TextEntry::make('next_vgp_date')
                            ->label('Prochaine VGP')
                            ->date('d/m/Y')
                            ->placeholder('Non définie'),
                        TextEntry::make('chantier.name')
                            ->label('Chantier d\'imputation')
                            ->placeholder('Aucun'),
                        TextEntry::make('grant_amount')
                            ->label('Subvention')
                            ->money('EUR')
                            ->visible(fn ($record) => $record->grant_amount > 0),
                        TextEntry::make('grant_name')
                            ->label('Origine de la subvention')
                            ->visible(fn ($record) => $record->grant_amount > 0),
                    ])->columns(2),

                Section::make('Tableau d\'amortissement prévisionnel')
                    ->schema([
                        ViewEntry::make('depreciations')
                            ->view('filament.immobilisation.infolists.components.depreciations-table')
                    ])
            ]);
    }
}
