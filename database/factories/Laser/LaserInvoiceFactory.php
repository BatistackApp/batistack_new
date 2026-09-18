<?php

namespace Database\Factories\Laser;

use App\Enums\Laser\InvoiceStatus;
use App\Models\Laser\LaserInvoice;
use App\Models\Laser\LaserOrder;
use App\Models\Tiers\ThirdParty;
use App\Support\ReferenceGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaserInvoiceFactory extends Factory
{
    protected $model = LaserInvoice::class;

    public function definition(): array
    {
        return [
            'client_id' => ThirdParty::factory(),
            'laser_order_id' => LaserOrder::factory(),
            'reference' => ReferenceGenerator::next('LFAC'),
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => 0,
            'total_tva' => 0,
            'total_ttc' => 0,
            'vat_rate' => 20,
            'due_date' => now()->addDays(30),
            'signature_hash' => null,
        ];
    }
}
