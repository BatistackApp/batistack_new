<?php

namespace App\Events\Commerce;

use Illuminate\Foundation\Events\Dispatchable;

class PaymentCancelledEvent
{
    use Dispatchable;

    public function __construct()
    {
    }
}
