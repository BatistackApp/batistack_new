<?php

namespace Database\Factories\Laser;

use App\Enums\Laser\OrderStatus;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserQuote;
use App\Models\Tiers\ThirdParty;
use App\Support\ReferenceGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaserOrderFactory extends Factory
{
    protected $model = LaserOrder::class;

    public function definition(): array
    {
        return [
            'client_id' => ThirdParty::factory(),
            'laser_quote_id' => null,
            'reference' => ReferenceGenerator::next('LAC'),
            'status' => OrderStatus::DRAFT,
            'total_ht' => 0,
            'total_ttc' => 0,
            'terms' => null,
        ];
    }
}
