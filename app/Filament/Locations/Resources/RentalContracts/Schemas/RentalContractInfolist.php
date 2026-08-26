<?php

namespace App\Filament\Locations\Resources\RentalContracts\Schemas;

use App\Models\Locations\RentalContract;
use App\Services\Locations\RentalCostService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RentalContractInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make([
                    Section::make('Informations du contrat')
                        ->schema([
                            TextEntry::make('reference')->label('Référence'),
                            TextEntry::make('name')->label('Désignation'),
                            TextEntry::make('supplier.name')->label('Fournisseur'),
                            TextEntry::make('chantier.name')->label('Chantier imputé'),
                        ])->columns(2),

                    Section::make('Détails des articles loués')
                        ->schema([
                            RepeatableEntry::make('lines')
                                ->label('')
                                ->schema([
                                    TextEntry::make('name')->label('Article'),
                                    TextEntry::make('quantity')->label('Qté'),
                                    TextEntry::make('unit_price_ht')->label('Prix U. HT')->money('EUR'),
                                    TextEntry::make('total_price_ht')->label('Total HT')->money('EUR'),
                                ])
                                ->columns(4),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Suivi & Facturation')
                        ->schema([
                            TextEntry::make('status')->label('Statut')
                                ->badge()
                                ->label('Statut'),
                            TextEntry::make('billing_period')
                                ->badge()
                                ->label('Période facturation'),
                            TextEntry::make('start_date')
                                ->date()
                                ->label('Début'),
                            TextEntry::make('end_date')
                                ->date()
                                ->label('Fin (prévue)'),
                        ]),

                    Section::make('Coût analytique')
                        ->schema([
                            TextEntry::make('daily_cost_ht')
                                ->money('EUR')
                                ->label('Coût journalier'),
                            TextEntry::make('cumulative_cost')
                                ->label('Coût cumulé (à ce jour)')
                                ->money('EUR')
                                ->state(function (RentalContract $record) {
                                    return app(RentalCostService::class)->getCumulativeCost($record);
                                })
                                ->color('danger'),
                        ]),

                    Section::make('Évaluation du fournisseur')
                        ->schema([
                            TextEntry::make('supplier_score')
                                ->label('Note')
                                ->formatStateUsing(fn ($state) => str_repeat('⭐', $state)." ($state/5)"),
                            TextEntry::make('supplier_feedback')
                                ->label('Commentaire'),
                        ])
                        ->visible(fn ($record) => $record->supplier_score !== null),
                ])->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
