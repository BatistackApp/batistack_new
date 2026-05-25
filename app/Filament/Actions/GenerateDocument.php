<?php

namespace App\Filament\Actions;

use App\Services\Commerce\CommerceDocumentationService;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class GenerateDocument
{
    public static function make(string $typeDocument)
    {
        return Action::make('regenerate')
            ->label('Générer le document pdf')
            ->icon(Phosphor::ArrowsSplit)
            ->action(function (Model $record, CommerceDocumentationService $service) use ($typeDocument) {
                match ($typeDocument) {
                    'devis_client' => $service->generateQuotePdf($record),
                    'commande_client' => $service->generateOrderPdf($record),
                    'situation_client' => $service->generateSituationPdf($record),
                    'livraison_client' => $service->generateInvoicePdf($record),
                    'etat_client' => $service->generateCustomerStatement($record->client_id, now()->startOfMonth(), now()->endOfMonth()),
                };
            });
    }
}
