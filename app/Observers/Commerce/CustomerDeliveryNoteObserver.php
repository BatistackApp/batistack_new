<?php

namespace App\Observers\Commerce;

use App\Enums\Commerce\DeliveryStatus;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Notifications\Commerce\OrderShippedNotification;
use App\Services\Commerce\CommerceDocumentationService;

class CustomerDeliveryNoteObserver
{
    public function updated(CustomerDeliveryNote $customerDeliveryNote): void
    {
        if ($customerDeliveryNote->wasChanged('status') && $customerDeliveryNote->status === DeliveryStatus::SHIPPED) {
            $customerDeliveryNote->client->primaryContact->notify(new OrderShippedNotification($customerDeliveryNote));
            app(CommerceDocumentationService::class)->generateDeliveryNotePdf($customerDeliveryNote);
        }
    }
}
