<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerInvoice extends ViewRecord
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('validated')
                ->label('Valider la facture')
                ->icon(Phosphor::CheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Valider la facture')
                ->modalDescription('Etes-vous sur de valider la facture ?')
                ->before(function (Model $record) {
                    $countItems = $record->items->count();
                    if ($countItems === 0) {
                        Notification::make()
                            ->danger()
                            ->title('Impossible de validée la facture si aucun produits/services n\'est à l\'intérieur !')
                            ->send();

                        return;
                    }
                })
                ->action(function (Model $record) {
                    $record->update([
                        'status' => InvoiceStatus::VALIDATED,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Facture Validée')
                        ->send();
                })
                ->visible(fn (Model $record) => $record->status === InvoiceStatus::DRAFT),
        ];
    }
}
