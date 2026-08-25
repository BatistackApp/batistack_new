<?php

namespace App\Filament\Customer\Resources\CustomerInvoices\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Filament\Customer\Resources\CustomerInvoices\CustomerInvoiceResource;
use App\Services\Commerce\CommerceDocumentationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerInvoice extends ViewRecord
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('payOnline')
                ->label('Payer en ligne')
                ->color('success')
                ->icon(Phosphor::CreditCard)
                ->visible(fn () => in_array($this->record->status, [
                    InvoiceStatus::VALIDATED,
                    InvoiceStatus::PAYMENT_IN_PROGRESS,
                    InvoiceStatus::PARTIALLY_PAID,
                ]))
                ->url(fn () => route('pay.invoice', [
                    'invoice' => $this->record->id,
                ], absolute: false))
                ->openUrlInNewTab(),

            ActionGroup::make([
                Action::make('downloadPdf')
                    ->label('Télécharger le PDF')
                    ->icon(Phosphor::ArrowDown)
                    ->action(fn () => $this->downloadPdf()),
            ]),
        ];
    }

    private function downloadPdf(): void
    {
        try {
            $service = app(CommerceDocumentationService::class);
            $path = $service->generateInvoicePdf($this->record);

            response()->download(storage_path("app/{$path}"))->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Erreur lors de la génération du PDF')
                ->body($e->getMessage())
                ->send();
        }
    }
}
