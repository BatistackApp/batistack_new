<?php

namespace App\Filament\Commerce\Resources\PurchaseOrders\Pages;

use App\Filament\Commerce\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;
}
