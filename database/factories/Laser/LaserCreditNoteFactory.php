<?php

namespace Database\Factories\Laser;

use App\Models\Laser\LaserCreditNote;
use App\Models\Laser\LaserInvoice;
use App\Models\Tiers\ThirdParty;
use App\Support\ReferenceGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaserCreditNoteFactory extends Factory
{
    protected $model = LaserCreditNote::class;

    public function definition(): array
    {
        return [
            'client_id' => ThirdParty::factory(),
            'laser_invoice_id' => LaserInvoice::factory(),
            'reference' => ReferenceGenerator::next('LAVO'),
            'status' => 'validated',
            'total_ht' => fake()->randomFloat(2, 10, 5000),
            'total_tva' => fake()->randomFloat(2, 2, 1000),
            'total_ttc' => fake()->randomFloat(2, 12, 6000),
            'reason' => fake()->sentence(),
        ];
    }
}
