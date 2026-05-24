<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Actions;

use App\Enums\Commerce\OrderStatus;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CancelAction
{
    public static function make(): Action
    {
        return Action::make('cancel')
            ->label('Annuler la commande')
            ->color('danger')
            ->icon(Phosphor::Prohibit)
            ->visible(fn (Model $record) => $record->status === OrderStatus::DRAFT || $record->status === OrderStatus::CONFIRMED)
            ->requiresConfirmation()
            ->action(function (Model $record) {
                $record->update([
                    'status' => OrderStatus::CANCELLED,
                ]);
            });
    }
}
