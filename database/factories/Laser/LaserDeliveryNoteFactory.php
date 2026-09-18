<?php

namespace Database\Factories\Laser;

use App\Enums\Laser\DeliveryStatus;
use App\Models\Laser\LaserDeliveryNote;
use App\Models\Laser\LaserOrder;
use App\Models\Tiers\ThirdParty;
use App\Support\ReferenceGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaserDeliveryNoteFactory extends Factory
{
    protected $model = LaserDeliveryNote::class;

    public function definition(): array
    {
        return [
            'client_id' => ThirdParty::factory(),
            'laser_order_id' => LaserOrder::factory(),
            'reference' => ReferenceGenerator::next('LBL'),
            'status' => DeliveryStatus::DRAFT,
            'delivery_date' => null,
        ];
    }
}
