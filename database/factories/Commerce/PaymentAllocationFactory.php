<?php

namespace Database\Factories\Commerce;

use App\Models\Commerce\Payment;
use App\Models\Commerce\PaymentAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PaymentAllocationFactory extends Factory
{
    protected $model = PaymentAllocation::class;

    public function definition(): array
    {
        return [
            'allocated_amount' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'payment_id' => Payment::factory(),
        ];
    }
}
