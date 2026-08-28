<?php

namespace App\Observers\Commerce;

use App\Enums\Commerce\OrderStatus;
use App\Jobs\Commerce\GenerateDocumentJob;
use App\Jobs\Commerce\GenerateManufacturingOrdersJob;
use App\Models\Commerce\CustomerOrder;

class CustomerOrderObserver
{
    public function saved(CustomerOrder $customerOrder): void
    {
        if ($customerOrder->isDirty('status') && $customerOrder->status === OrderStatus::CONFIRMED) {
            GenerateDocumentJob::dispatch('order', $customerOrder)->afterCommit();

            // Si la commande n'a pas encore généré d'OF, on les génère
            if ($customerOrder->manufacturingOrders()->count() === 0) {
                GenerateManufacturingOrdersJob::dispatch($customerOrder)->afterCommit();
            }
        }
    }
}
