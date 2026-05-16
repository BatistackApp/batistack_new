<?php

namespace Database\Seeders;

use App\Models\Tiers\Address;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Seeder;

class TiersSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = ThirdParty::factory(rand(10, 50))->create();

        foreach ($tiers as $tier) {
            Address::factory()->create([
                'third_party_id' => $tier->id,
            ]);

            Contact::factory()->create([
                'third_party_id' => $tier->id,
            ]);
        }

    }
}
