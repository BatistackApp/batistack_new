<?php

namespace Database\Factories\Commerce;

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerOrder;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerDeliveryNoteFactory extends Factory
{
    protected $model = CustomerDeliveryNote::class;

    public function definition(): array
    {
        return [
            'reference' => $this->faker->word(),
            'status' => $this->faker->word(),
            'delivery_date' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'client_id' => ThirdParty::factory(),
            'chantier_id' => Chantier::factory(),
            'customer_order_id' => CustomerOrder::factory(),
        ];
    }
}
