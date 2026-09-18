<?php

namespace Database\Factories\Laser;

use App\Enums\Laser\QuoteStatus;
use App\Models\Laser\LaserQuote;
use App\Models\Tiers\ThirdParty;
use App\Services\Laser\LaserQuoteService;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaserQuoteFactory extends Factory
{
    protected $model = LaserQuote::class;

    public function definition(): array
    {
        return [
            'client_id' => ThirdParty::factory(),
            'reference' => app(LaserQuoteService::class)->generateReference(),
            'status' => QuoteStatus::DRAFT,
            'total_ht' => 0,
            'total_ttc' => 0,
            'expires_at' => now()->addDays(30),
        ];
    }
}
