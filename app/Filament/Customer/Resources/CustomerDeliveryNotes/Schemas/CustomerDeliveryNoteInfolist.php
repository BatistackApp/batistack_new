<?php

namespace App\Filament\Customer\Resources\CustomerDeliveryNotes\Schemas;

use App\Enums\Commerce\DeliveryStatus;
use App\Models\Commerce\CustomerDeliveryNote;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use Webkul\ProgressStepper\Enums\Size;
use Webkul\ProgressStepper\Forms\Components\ProgressStepper;

class CustomerDeliveryNoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ProgressStepper::make('status')
                    ->hiddenLabel()
                    ->optionsFromEnum(DeliveryStatus::class)
                    ->size(Size::Large)
                    ->columnSpanFull(),

                Section::make()
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('reference')->label('Référence')->icon(Phosphor::Hash),
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
                            ->url(fn (CustomerDeliveryNote $record) => "/customer/customer-delivery-notes/{$record->id}"),

                        TextEntry::make('status')
                            ->label('Etat de la commande')
                            ->badge(),
                    ]),
            ]);
    }
}
