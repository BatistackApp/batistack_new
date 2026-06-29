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
                    ])->columnSpanFull(),
            ]);
    }
}
