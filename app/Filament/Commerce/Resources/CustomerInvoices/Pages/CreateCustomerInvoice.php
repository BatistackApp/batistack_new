<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Pages;

use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use App\Models\Commerce\CustomerOrder;
use App\Services\Commerce\CustomerOrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomerInvoice extends CreateRecord
{
    protected static string $resource = CustomerInvoiceResource::class;
    protected static ?string $title = 'Nouvelle facture';

    /**
     * @throws \Throwable
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CustomerOrderService::class)->createInvoice(
            order: CustomerOrder::query()->findOrFail($data['order_id']),
            type: $data['type'],
            responsable: auth()->user(),
            situation: isset($data['customer_situation_id']) ? \App\Models\Commerce\CustomerSituation::find($data['customer_situation_id']) : null,
            acompteAmount: $data['amountAcompte'] ?? null,
        );
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.commerce.resources.customer-invoices.index');
    }
}
