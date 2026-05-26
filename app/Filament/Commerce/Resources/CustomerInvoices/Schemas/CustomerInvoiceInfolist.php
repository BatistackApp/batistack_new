<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Schemas;

use App\Enums\Commerce\InvoiceType;
use App\Enums\Tiers\AddressType;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
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
                        Grid::make(1)
                            ->columnSpan(8)
                            ->schema([
                                Section::make()
                                    ->columnSpanFull()
                                    ->columns(4)
                                    ->schema([
                                        TextEntry::make('reference')
                                            ->label('Référence')
                                            ->icon(Phosphor::Hash),

                                        TextEntry::make('client.legal_name')
                                            ->label('Client')
                                            ->icon(Phosphor::User),

                                        TextEntry::make('type')
                                            ->label('Type'),

                                        TextEntry::make('order.reference')
                                            ->label('Commande')
                                            ->icon(Phosphor::ShoppingBag)
                                            ->visible(fn (Model $record) => $record->order),

                                        TextEntry::make('situation.number')
                                            ->label('Situation N°')
                                            ->visible(fn (Model $record) => $record->situation && $record->type === InvoiceType::SITUATION),

                                        TextEntry::make('due_date')
                                            ->date('d/m/Y')
                                            ->icon(fn (Model $record) => $record->is_overdue ? Phosphor::WarningCircle : null)
                                            ->iconColor('warning')
                                            ->color(fn (Model $record) => $record->is_overdue ? 'danger' : 'success')
                                            ->tooltip(fn (Model $record) => $record->is_overdue ? 'Facture en retard' : null)
                                            ->label('Date d\'échéance'),

                                        TextEntry::make('cancellation_reason')
                                            ->label("Raison de l'annulation")
                                            ->visible(fn (Model $record) => $record->cancellation_reason),
                                    ]),

                                Grid::make(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        Section::make('Adresse de Facturation')
                                            ->visible(function (Model $record) {
                                                return $record->client->addresses()->where('type', AddressType::BILLING)->exists();
                                            })
                                            ->schema([
                                                TextEntry::make('client.addresses')
                                                    ->hiddenLabel()
                                                    ->formatStateUsing(function (Model $record) {
                                                        $address = $record->client->addresses()->where('type', AddressType::BILLING)->first();

                                                        return "{$address->street} <br>{$address->zip_code} {$address->city}";
                                                    })
                                                    ->html(),
                                            ]),

                                        Section::make('Adresse de Livraison')
                                            ->visible(function (Model $record) {
                                                return $record->client->addresses()->where('type', AddressType::DELIVERY)->exists();
                                            })
                                            ->schema([
                                                TextEntry::make('client.addresses')
                                                    ->hiddenLabel()
                                                    ->formatStateUsing(function (Model $record) {
                                                        $address = $record->client->addresses()->where('type', AddressType::DELIVERY)->first();

                                                        return "{$address->street} <br>{$address->zip_code} {$address->city}";
                                                    })

                                                    ->html(),
                                            ]),

                                    ]),
                            ]),

                        Grid::make()
                            ->columnSpan(4)
                            ->schema([
                                ViewEntry::make('status')
                                    ->columnSpanFull()
                                    ->view('filament.commerce.infolists.invoice_status_card'),

                                ViewEntry::make('total')
                                    ->columnSpanFull()
                                    ->view('filament.commerce.infolists.invoice_total'),
                            ]),
                    ]),
            ]);
    }
}
