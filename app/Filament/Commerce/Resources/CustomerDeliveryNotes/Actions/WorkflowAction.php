<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Actions;

use App\Enums\Commerce\DeliveryStatus;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class WorkflowAction
{
    public static function make(string $action): Action
    {
        $dataWorkflows = match ($action) {
            'shipping' => [
                'label' => 'Envoyer la commande',
                'color' => 'info',
                'icon' => Phosphor::ArrowsLeftRight,
                'key' => 'shipping',
                'modal' => [
                    'required' => true,
                    'heading' => 'Validation de la livraison',
                    'description' => "Vous allez valider l'envoie de la commande au client, aucun changement ne pourra être apporter. Etes-vous sur ?",
                ],
                'visible' => fn (Model $record) => $record->status === DeliveryStatus::PREPARATION,
            ],
            'delivered' => [
                'label' => 'Commande receptionner',
                'color' => 'success',
                'icon' => Phosphor::CheckCircle,
                'key' => 'delivered',
                'modal' => [
                    'required' => false,
                    'heading' => null,
                    'description' => null,
                ],
                'visible' => fn (Model $record) => $record->status === DeliveryStatus::SHIPPED,
            ]
        };

        return Action::make($dataWorkflows['key'])
            ->label($dataWorkflows['label'])
            ->icon($dataWorkflows['icon'])
            ->color($dataWorkflows['color'])
            ->requiresConfirmation($dataWorkflows['modal']['required'])
            ->modalHeading($dataWorkflows['modal']['heading'])
            ->modalDescription($dataWorkflows['modal']['description'])
            ->visible($dataWorkflows['visible']);
    }
}
