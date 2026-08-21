<?php

namespace App\Filament\Locations\Resources\RentalContracts\Schemas;

use App\Enums\Locations\RentalBillingPeriod;
use App\Enums\Locations\RentalStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RentalContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Informations du contrat')
                            ->schema([
                                Select::make('supplier_id')
                                    ->relationship('supplier', 'name', fn ($query) => $query->where('type', \App\Enums\Tiers\ThirdPartyType::SUPPLIER))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Fournisseur')
                                    ->hintAction(
                                        Action::make('comparator')
                                            ->label('Comparer les prix')
                                            ->icon('heroicon-m-calculator')
                                            ->url(fn () => \App\Filament\Locations\Pages\Locations\SupplierPriceComparator::getUrl(), shouldOpenInNewTab: true)
                                    ),

                                Select::make('chantier_id')->label('Chantier')
                                    ->relationship('chantier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('Chantier d\'imputation'),

                                TextInput::make('reference')->label('Référence')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->label('Référence')
                                    ->maxLength(255),

                                TextInput::make('name')->label('Nom')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nom / Désignation courte'),
                            ])->columns(2),

                        Section::make('Lignes de location')
                            ->schema([
                                Repeater::make('lines')
                                    ->relationship()
                                    ->schema([
                                        TextInput::make('name')->label('Nom')
                                            ->required()
                                            ->label('Désignation de l\'article'),

                                        TextInput::make('description')->label('Description')
                                            ->label('Description')
                                            ->nullable(),

                                        TextInput::make('quantity')->label('Quantité')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->label('Quantité'),

                                        TextInput::make('unit_price_ht')
                                            ->required()
                                            ->numeric()
                                            ->label('Prix Unitaire HT')
                                            ->suffix('€'),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('Ajouter une ligne de location')
                                    ->collapsible()
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Période et Statut')
                            ->schema([
                                Select::make('status')->label('Statut')
                                    ->options(RentalStatus::class)
                                    ->default(RentalStatus::DRAFT)
                                    ->label('Statut')
                                    ->required(),

                                DatePicker::make('start_date')
                                    ->label('Date de début')
                                    ->required(),

                                DatePicker::make('expected_end_date')
                                    ->label('Date de fin prévue')
                                    ->nullable(),

                                DatePicker::make('end_date')
                                    ->label('Date de fin réelle (restitution)')
                                    ->nullable(),
                            ]),

                        Section::make('Facturation & Coûts')
                            ->schema([
                                Select::make('billing_period')
                                    ->options(RentalBillingPeriod::class)
                                    ->default(RentalBillingPeriod::MONTHLY)
                                    ->required()
                                    ->label('Période de facturation'),

                                TextInput::make('daily_cost_ht')
                                    ->required()
                                    ->numeric()
                                    ->label('Coût journalier HT (pour Analytique)')
                                    ->suffix('€')
                                    ->helperText('Ce montant sera imputé chaque jour sur le chantier.'),

                                TextInput::make('daily_penalty_rate')
                                    ->numeric()
                                    ->label('Majoration / Pénalité de retard (par jour)')
                                    ->suffix('€')
                                    ->helperText('Appliqué si la date de fin prévue est dépassée.'),
                            ])
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
