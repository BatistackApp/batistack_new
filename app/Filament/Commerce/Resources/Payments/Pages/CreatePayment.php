<?php

namespace App\Filament\Commerce\Resources\Payments\Pages;

use App\Filament\Commerce\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
}
