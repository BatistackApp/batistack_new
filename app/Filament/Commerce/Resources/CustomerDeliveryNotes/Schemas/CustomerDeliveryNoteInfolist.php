<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Schemas;

use App\Models\Commerce\CustomerDeliveryNote;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerDeliveryNoteInfolist
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
                            ->columns(3)
                            ->schema([
                                TextEntry::make('reference')
                                    ->label('Référence')
                                    ->weight(FontWeight::Bold)
                                    ->icon(Phosphor::Hash),

                                TextEntry::make('client.legal_name')
                                    ->label('Client')
                                    ->icon(Phosphor::User),

                                TextEntry::make('chantier.reference')
                                    ->label('Chantier')
                                    ->icon(Phosphor::Crane)
                                    ->formatStateUsing(fn (CustomerDeliveryNote $record) => $record->chantier->reference.' - '.$record->chantier->name)
                                    ->visible(fn (CustomerDeliveryNote $record) => $record->chantier),

                                TextEntry::make('order.reference')
                                    ->label('Commande affilié')
                                    ->icon(Phosphor::ShoppingBag)
                                    ->visible(fn (CustomerDeliveryNote $record) => $record->order)
                                    ->color('primary')
                                    ->url(fn (CustomerDeliveryNote $record) => route('filament.commerce.resources.customer-orders.view', ['record' => $record->order])),
                            ]),

                        ViewEntry::make('status')
                            ->view('filament.commerce.delivery_status_card', ['data' => 'OK'])
                            ->columnSpan(4),
                    ]),
            ]);
    }
}
