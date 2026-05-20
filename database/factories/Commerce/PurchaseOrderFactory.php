<?php

namespace Database\Factories\Commerce;

use App\Enums\Commerce\OrderStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'supplier_id' => ThirdParty::factory()->state(['type' => 'supplier']),
            'chantier_id' => Chantier::factory(),
            'reference' => 'BC-'.now()->year.'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => OrderStatus::CONFIRMED,
            'total_ht' => $this->faker->randomFloat(2, 500, 20000),
            'ordered_at' => now(),
        ];
    }
}
