<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Schemas;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\VigilanceService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ThirdPartyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Détails du Tiers')
                    ->tabs([
                        Tabs\Tab::make('Identité Légale')
                            ->icon(Phosphor::IdentificationCard)
                            ->schema([
                                Section::make('Enregistrement')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('name')->label('Nom Commercial')->weight('bold'),
                                        TextEntry::make('legal_name')->label('Raison Sociale'),
                                        TextEntry::make('type')->badge(),
                                        TextEntry::make('siret')->label('SIRET')->fontFamily('mono'),
                                        TextEntry::make('vat_number')->label('TVA')->fontFamily('mono'),
                                        IconEntry::make('is_active')->label('Actif')->boolean(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Répertoire')
                            ->icon(Phosphor::MapPin)
                            ->schema([
                                RepeatableEntry::make('addresses')
                                    ->grid(2)
                                    ->schema([
                                        TextEntry::make('type')->badge(),
                                        TextEntry::make('full_address')
                                            ->icon(Phosphor::MapTrifold),
                                    ]),
                            ]),

                        Tabs\Tab::make('Conformité')
                            ->icon(Phosphor::ShieldCheck)
                            ->schema([
                                Section::make('Moteur de Vigilance')
                                    ->schema([
                                        TextEntry::make('compliance')
                                            ->visible(fn (ThirdParty $record) => $record->type === ThirdPartyType::SUBCONTRACTOR)
                                            ->label('État global')
                                            ->getStateUsing(fn ($record) => $record->compliant_status['compliant'] ? 'CONFORME' : 'NON-CONFORME')
                                            ->badge()
                                            ->color(fn ($state) => $state === 'CONFORME' ? 'success' : 'danger')
                                            ->icon(fn ($state) => $state === 'CONFORME' ? Phosphor::CheckCircle : Phosphor::Warning),
                                            
                                        TextEntry::make('supplier_score')
                                            ->label('Score de Fiabilité')
                                            ->badge()
                                            ->color(fn ($state) => $state === null ? 'gray' : ($state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger')))
                                            ->formatStateUsing(fn ($state) => $state !== null ? $state . '/100' : 'Non évalué'),

                                        TextEntry::make('last_siren_sync_at')
                                            ->label('Dernière synchro SIREN')
                                            ->since(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Vue 360 - Financier & Opérationnel')
                            ->icon(Phosphor::ChartPie)
                            ->schema([
                                Section::make('En-cours Financier (Ventes & Achats)')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('sales_outstanding')
                                            ->label('Factures Ventes Impayées (Client)')
                                            ->state(function (ThirdParty $record) {
                                                if (!class_exists(\App\Models\Commerce\CustomerInvoice::class)) return 'N/A';
                                                
                                                $invoices = \App\Models\Commerce\CustomerInvoice::forClient($record)->unpaid()->get();
                                                $count = $invoices->count();
                                                $amount = $invoices->sum('amount_remaining');
                                                
                                                return "{$count} facture(s) - " . number_format($amount, 2, ',', ' ') . " €";
                                            })
                                            ->badge()
                                            ->color(fn ($state) => str_starts_with($state, '0') ? 'success' : 'warning'),
                                            
                                        TextEntry::make('purchases_outstanding')
                                            ->label('Factures Achats Impayées (Fournisseur)')
                                            ->state(function (ThirdParty $record) {
                                                if (!class_exists(\App\Models\Commerce\SupplierInvoice::class)) return 'N/A';
                                                
                                                $invoices = \App\Models\Commerce\SupplierInvoice::where('supplier_id', $record->id)
                                                    ->whereNotIn('status', ['draft', 'paid', 'canceled'])
                                                    ->get();
                                                $count = $invoices->count();
                                                // Assuming SupplierInvoice has amount_ttc, if it has partial payments, it might be different, but let's sum amount_ttc for now.
                                                $amount = $invoices->sum('amount_ttc');
                                                
                                                return "{$count} facture(s) - " . number_format($amount, 2, ',', ' ') . " €";
                                            })
                                            ->badge()
                                            ->color(fn ($state) => str_starts_with($state, '0') ? 'success' : 'danger'),
                                    ]),
                                Section::make('Activité Opérationnelle')
                                    ->schema([
                                        TextEntry::make('active_chantiers')
                                            ->label('Chantiers Actifs associés')
                                            ->state(function (ThirdParty $record) {
                                                if (!class_exists(\App\Models\Chantiers\Chantier::class)) return 'N/A';
                                                
                                                $clientCount = \App\Models\Chantiers\Chantier::where('client_id', $record->id)
                                                    ->whereNotIn('status', ['completed', 'canceled'])
                                                    ->count();
                                                    
                                                $subcontractorCount = $record->chantiers()
                                                    ->whereNotIn('status', ['completed', 'canceled'])
                                                    ->count();
                                                    
                                                $total = $clientCount + $subcontractorCount;
                                                return "{$total} chantier(s)";
                                            })
                                            ->badge()
                                            ->color('info'),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
