<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Actions;

use Filament\Support\Enums\Width;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PrinterAction
{
    public static function make(): MediaAction
    {
        return MediaAction::make()
            ->label('Imprimer PDF')
            ->icon(Phosphor::Printer)
            ->mediaType(MediaAction::TYPE_PDF)
            ->modalWidth(Width::Container)
            ->media(fn (Model $record) => Storage::url('documents/commerce/orders/commande_'.$record->reference.'.pdf'));
    }
}
