<?php

namespace App\Filament\Actions;

use Filament\Support\Enums\Width;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Database\Eloquent\Model;

class PrinterAction
{
    /**
     * @param string $typeDocument
     * @return MediaAction
     */
    public static function make(string $typeDocument): MediaAction
    {
        return MediaAction::make('viewPrinter')
            ->label('Voir PDF')
            ->mediaType(MediaAction::TYPE_PDF)
            ->modalWidth(Width::Container)
            ->media(function (Model $record) use ($typeDocument) {
                return match ($typeDocument) {
                    'devis' => \Storage::disk('public')->url("documents/commerce/quotes/devis_{$record->reference}.pdf"),
                    'commande' => \Storage::disk('public')->url("documents/commerce/orders/commande_{$record->reference}.pdf"),
                    'livraison' => \Storage::disk('public')->url("documents/commerce/deliveries/bl_{$record->reference}.pdf"),
                    'situation' => \Storage::disk('public')->url("documents/commerce/situations/situation_{$record->number}_{$record->chantier->reference}.pdf"),
                    'facture' => \Storage::disk('public')->url("documents/commerce/invoices/facture_{$record->reference}.pdf"),
                    'avoir' => \Storage::disk('public')->url("documents/commerce/credit-note/avoir_{$record->reference}.pdf"),
                };
            });
    }
}
