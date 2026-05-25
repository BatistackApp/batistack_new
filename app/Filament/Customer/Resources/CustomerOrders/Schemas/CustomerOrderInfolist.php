<?php

namespace App\Filament\Customer\Resources\CustomerOrders\Schemas;

use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\CustomerOrder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use Webkul\ProgressStepper\Enums\Size;
use Webkul\ProgressStepper\Forms\Components\ProgressStepper;

class CustomerOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ProgressStepper::make('status')
                    ->optionsFromEnum(OrderStatus::class)
                    ->size(Size::Large)
                    ->columnSpanFull()
                    ->hiddenLabel(),

                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->columnSpan(8)
                            ->columns(4)
                            ->schema([
                                TextEntry::make('reference')
                                    ->label('Numéro')
                                    ->icon(Phosphor::Hash),

                                TextEntry::make('chantier.name')
                                    ->label('Chantier')
                                    ->icon(Phosphor::HardHat),

                                TextEntry::make('quote.reference')
                                    ->label('Lié au devis')
                                    ->icon(Phosphor::FileText)
                                    ->visible(fn (CustomerOrder $record) => $record->quote !== null)
                                    ->url(fn (CustomerOrder $record) => "/customer/customer-quotes/{$record->quote->id}"),

                                TextEntry::make('status')
                                    ->label('Etat de la commande'),

                            ]),

                        ViewEntry::make('total')
                            ->columnSpan(4)
                            ->view('filament.customer.infolists.order_total'),
                    ]),
            ]);
    }
}
