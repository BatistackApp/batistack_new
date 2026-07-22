<?php

namespace App\Observers\Commerce;

use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\CustomerOrder;
use App\Services\Commerce\CommerceDocumentationService;

class CustomerOrderObserver
{
    public function saved(CustomerOrder $customerOrder): void
    {
        if ($customerOrder->isDirty('status') && $customerOrder->status === OrderStatus::CONFIRMED) {
            \App\Jobs\Commerce\GenerateDocumentJob::dispatch('order', $customerOrder)->afterCommit();
            
            // Si la commande n'a pas encore généré d'OF, on les génère
            if ($customerOrder->manufacturingOrders()->count() === 0) {
                \App\Jobs\Commerce\GenerateManufacturingOrdersJob::dispatch($customerOrder)->afterCommit();
            }
        }
    }
}
