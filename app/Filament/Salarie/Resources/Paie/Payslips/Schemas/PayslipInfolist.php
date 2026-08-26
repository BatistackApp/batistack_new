<?php

namespace App\Filament\Salarie\Resources\Paie\Payslips\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class PayslipInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Résumé du bulletin')
                    ->columnSpanFull()
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('period')
                            ->label('Période')
                            ->size('lg')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('status')->label('Statut')
                            ->label('Statut')
                            ->badge(),
                        TextEntry::make('payment_date')
                            ->label('Date de virement')
                            ->date('d/m/Y')
                            ->placeholder('Non défini'),
                    ]),

                Section::make('Rémunération')
                    ->columnSpanFull()
                    ->icon('heroicon-o-currency-euro')
                    ->columns(2)
                    ->schema([
                        Group::make([
                            Section::make('Temps de travail')
                                ->schema([
                                    TextEntry::make('base_hours')
                                        ->label('Heures de base')
                                        ->suffix(' h'),
                                    TextEntry::make('hourly_rate')
                                        ->label('Taux horaire')
                                        ->money('EUR'),
                                    TextEntry::make('overtime_hours')
                                        ->label('Heures supplémentaires')
                                        ->suffix(' h')
                                        ->visible(fn ($record) => $record->overtime_hours > 0),
                                    TextEntry::make('overtime_amount')
                                        ->label('Montant Heures Sup.')
                                        ->money('EUR')
                                        ->visible(fn ($record) => $record->overtime_amount > 0),
                                ])->columns(2),

                            Section::make('Indemnités & Primes')
                                ->schema([
                                    TextEntry::make('gd_allowance_amount')
                                        ->label('Grands Déplacements')
                                        ->money('EUR')
                                        ->visible(fn ($record) => $record->gd_allowance_amount > 0),
                                    TextEntry::make('meal_allowance_amount')
                                        ->label('Paniers Repas')
                                        ->money('EUR')
                                        ->visible(fn ($record) => $record->meal_allowance_amount > 0),
                                    TextEntry::make('expense_reports_amount')
                                        ->label('Remboursement Frais')
                                        ->money('EUR')
                                        ->visible(fn ($record) => $record->expense_reports_amount > 0),
                                ])->columns(2),
                        ])->columnSpan(1),

                        Group::make([
                            Section::make('Totaux')
                                ->schema([
                                    TextEntry::make('gross_salary')
                                        ->label('Salaire Brut')
                                        ->money('EUR'),
                                    TextEntry::make('net_social')
                                        ->label('Net Social')
                                        ->money('EUR'),
                                    TextEntry::make('taxable_net')
                                        ->label('Net Imposable')
                                        ->money('EUR'),
                                    TextEntry::make('pas_amount')
                                        ->label('Prélèvement à la source (PAS)')
                                        ->money('EUR')
                                        ->helperText(fn ($record) => 'Taux : '.$record->pas_rate.'%'),
                                ])->columns(2),

                            Section::make('Net à Payer')
                                ->schema([
                                    TextEntry::make('net_paid')
                                        ->label('Total Net Payé (après acompte)')
                                        ->money('EUR')
                                        ->size('lg')
                                        ->weight(FontWeight::Bold)
                                        ->color('success'),
                                ]),
                        ])->columnSpan(1),
                    ]),
            ]);
    }
}
