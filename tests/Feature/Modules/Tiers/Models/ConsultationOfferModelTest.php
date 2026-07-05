<?php

namespace Tests\Feature\Modules\Tiers\Models;

use App\Models\Tiers\Consultation;
use App\Models\Tiers\ConsultationOffer;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ConsultationOffer - Relations', function () {
    test('appartient à une consultation', function () {
        $consultation = Consultation::factory()->create();
        $offer = ConsultationOffer::factory()->create(['consultation_id' => $consultation->id]);

        expect($offer->consultation->id)->toBe($consultation->id);
    });

    test('appartient à un tiers', function () {
        $thirdParty = ThirdParty::factory()->create();
        $offer = ConsultationOffer::factory()->create(['third_party_id' => $thirdParty->id]);

        expect($offer->thirdParty->id)->toBe($thirdParty->id);
    });
});
