<?php

namespace App\Filament\Customer\Resources\CustomerInvoices\Schemas;

use App\Models\Commerce\CustomerInvoice;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerInvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->columnSpan(8)
                            ->columns(4)
                            ->schema([
                                TextEntry::make('reference')
                                    ->label('Référence')
                                    ->icon(Phosphor::Hash),

                                TextEntry::make('type')
                                    ->label('Type')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state->getLabel()),

                                TextEntry::make('chantier.name')
                                    ->label('Chantier')
                                    ->icon(Phosphor::HardHat)
                                    ->placeholder('—'),

                                TextEntry::make('order.reference')
                                    ->label('Commande liée')
                                    ->icon(Phosphor::FileText)
                                    ->placeholder('—'),

                                TextEntry::make('due_date')
                                    ->label('Date d\'échéance')
                                    ->date('d/m/Y')
                                    ->icon(Phosphor::Calendar)
                                    ->color(fn ($state, CustomerInvoice $record): ?string => $record->is_overdue ? 'danger' : null),

                                TextEntry::make('sent_at')
                                    ->label('Date d\'envoi')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon(Phosphor::PaperPlaneTilt)
                                    ->placeholder('—'),

                                TextEntry::make('status')
                                    ->label('Statut')
                                    ->badge(),

                                TextEntry::make('dunning_level')
                                    ->label('Niveau de relance')
                                    ->icon(Phosphor::Bell)
                                    ->placeholder('Aucune')
                                    ->visible(fn (CustomerInvoice $record) => $record->dunning_level > 0),
                            ]),

                        ViewEntry::make('status')
                            ->columnSpan(4)
                            ->view('filament.commerce.infolists.invoice_status_card'),

                        ViewEntry::make('total')
                            ->columnSpan(4)
                            ->view('filament.commerce.infolists.invoice_total'),
                    ]),

                Section::make('Détails du paiement')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('total_ht')
                            ->label('Total HT')
                            ->money('EUR'),

                        TextEntry::make('total_tva')
                            ->label('Total TVA')
                            ->money('EUR'),

                        TextEntry::make('total_allocated')
                            ->label('Montant payé')
                            ->getStateUsing(fn (CustomerInvoice $record) => $record->total_allocated)
                            ->money('EUR')
                            ->color(fn (CustomerInvoice $record): string => $record->is_fully_paid ? 'success' : 'warning'),

                        TextEntry::make('amount_remaining')
                            ->label('Solde dû')
                            ->getStateUsing(fn (CustomerInvoice $record) => $record->amount_remaining)
                            ->money('EUR')
                            ->color(fn (CustomerInvoice $record): string => $record->amount_remaining > 0 ? 'danger' : 'success'),
                    ]),
            ]);
    }
}
