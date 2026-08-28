<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Actions;

use App\Enums\Commerce\OrderStatus;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ConfirmedAction
{
    public static function make(): Action
    {
        return Action::make('confirmedOrder')
            ->label('Confirmer la commande')
            ->color('success')
            ->icon(Phosphor::CheckCircle)
            ->visible(fn (Model $record) => $record->status === OrderStatus::DRAFT)
            ->requiresConfirmation()
            ->modalHeading('Confirmer la commande')
            ->modalDescription('La confirmation de la commande bloquera toutes modifications de celle-ci, etes-vous sur ?')
            ->action(function (Model $record) {
                $record->update([
                    'status' => OrderStatus::CONFIRMED,
                ]);
            });
    }
}
