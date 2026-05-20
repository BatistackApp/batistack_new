<?php

namespace Database\Factories\Commerce;

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SubcontractorSituationFactory extends Factory
{
    protected $model = SubcontractorSituation::class;

    public function definition(): array
    {
        return [
            'reference' => $this->faker->word(),
            'progress_percentage' => $this->faker->randomNumber(),
            'total_ht' => $this->faker->randomFloat(),
            'retenue_garantie_amount' => $this->faker->randomFloat(),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'third_party_id' => ThirdParty::factory(),
            'chantier_id' => Chantier::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
        ];
    }
}
