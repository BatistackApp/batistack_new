<?php

namespace Database\Factories\Locations;

use App\Enums\Locations\InternalRentalInvoiceStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Locations\InternalRentalInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalRentalInvoice>
 */
class InternalRentalInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fixedAsset = FixedAsset::factory()->create();
        $days = $this->faker->numberBetween(1, 30);
        $dailyRate = $this->faker->randomFloat(2, 10, 100);

        return [
            'fixed_asset_id' => $fixedAsset->id,
            'chantier_id' => $fixedAsset->chantier_id ?? Chantier::factory(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'days' => $days,
            'daily_rate' => $dailyRate,
            'amount_ht' => round($dailyRate * $days, 2),
            'status' => InternalRentalInvoiceStatus::DRAFT,
            'billing_key' => 'INT-'.$fixedAsset->id.'-'.now()->format('Ym'),
        ];
    }
}
