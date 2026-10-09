<?php

namespace App\Filament\Customer\Resources\CustomerInvoices\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Filament\Customer\Resources\CustomerInvoices\CustomerInvoiceResource;
use App\Services\Commerce\CommerceDocumentationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
                ->url(fn () => URL::signedRoute('pay.invoice', [
                    'invoice' => $this->record->id,
                ]))
                ->openUrlInNewTab(),

            ActionGroup::make([
                Action::make('downloadPdf')
                    ->label('Télécharger le PDF')
                    ->icon(Phosphor::ArrowDown)
                    ->action(fn () => $this->downloadPdf()),
            ]),
        ];
    }

    private function downloadPdf(): ?BinaryFileResponse
    {
        try {
            $service = app(CommerceDocumentationService::class);
            $path = $service->generateInvoicePdf($this->record);

            return response()->download(storage_path("app/{$path}"));
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Erreur lors de la génération du PDF')
                ->body($e->getMessage())
                ->send();

            return null;
        }
    }
}
