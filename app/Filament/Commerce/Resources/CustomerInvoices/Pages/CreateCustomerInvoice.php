<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Pages;

use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Services\Commerce\CustomerOrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateCustomerInvoice extends CreateRecord
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected static ?string $title = 'Nouvelle facture';

    /**
     * @throws \Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CustomerOrderService::class)->createInvoice(
                order: CustomerOrder::query()->findOrFail($data['order_id']),
                type: $data['type'],
                responsable: auth()->user(),
                situation: isset($data['customer_situation_id']) ? CustomerSituation::find($data['customer_situation_id']) : null,
                acompteAmount: $data['amountAcompte'] ?? null,
            );
        } catch (\Throwable $exception) {
            Log::emergency($exception->getMessage());
            throw $exception;
        }
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.commerce.resources.customer-invoices.index');
    }
}
