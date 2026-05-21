<?php

namespace App\Filament\Commerce\Resources\Payments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de paiement')
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference')
                            ->label('Référence de paiement')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'in' => 'Encaissement (Client)',
                                'out' => 'Décaissement (Fournisseur)',
                            ])
                            ->required()
                            ->native(false),

                        Select::make('method')
                            ->label('Moyen de paiement')
                            ->options([
                                'bank_transfer' => 'Virement bancaire',
                                'check' => 'Chèque',
                                'cash' => 'Espèces',
                                'credit_card' => 'Carte bancaire',
                                'other' => 'Autre',
                            ])
                            ->required()
                            ->native(false),

                        DatePicker::make('payment_date')
                            ->label('Date de paiement')
                            ->required(),

                        TextInput::make('amount')
                            ->label('Montant')
                            ->numeric()
                            ->required()
                            ->prefix('€'),

                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'completed' => 'Complété',
                                'cancelled' => 'Annulé',
                            ])
                            ->required()
                            ->native(false),
                    ]),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Observations')
                            ->rows(3),
                    ]),

                Section::make('Lettrage')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('allocations')
                            ->relationship()
                            ->columns(3)
                            ->schema([
                                Select::make('payable_type')
                                    ->label('Type de document')
                                    ->options([
                                        'App\Models\Commerce\CustomerInvoice' => 'Facture client',
                                        'App\Models\Commerce\SupplierInvoice' => 'Facture fournisseur',
                                        'App\Models\Commerce\CustomerSituation' => 'Situation de travaux',
                                    ])
                                    ->required()
                                    ->native(false),

                                TextInput::make('payable_id')
                                    ->label('N° de facture/situation')
                                    ->required(),

                                TextInput::make('allocated_amount')
                                    ->label('Montant alloué')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€'),
                            ]),
                    ]),
            ]);
    }
}
