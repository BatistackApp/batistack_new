<?php

namespace App\Events\Commerce;

use App\Models\Commerce\Payment;
use Illuminate\Foundation\Events\Dispatchable;

class PaymentCancelledEvent
{
    use Dispatchable;

    public function __construct(public Payment $payment, public string $reason)
    {
    }
}
