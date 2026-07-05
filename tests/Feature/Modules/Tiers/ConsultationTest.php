<?php

use App\Models\Chantiers\Chantier;
use App\Models\Tiers\Consultation;
use App\Models\Tiers\ConsultationOffer;
use App\Models\Tiers\ThirdParty;
use Carbon\Carbon;

it('creates a consultation and an offer', function () {
    $chantier = Chantier::factory()->create();
    $thirdParty = ThirdParty::factory()->create();

    $consultation = Consultation::create([
        'chantier_id' => $chantier->id,
        'title' => 'Plomberie Lot 1',
        'description' => 'Installation des sanitaires',
        'deadline' => Carbon::now()->addDays(15),
        'status' => 'published',
    ]);

    expect($consultation->id)->not->toBeNull()
        ->and($consultation->chantier_id)->toBe($chantier->id);

    $offer = ConsultationOffer::create([
        'consultation_id' => $consultation->id,
        'third_party_id' => $thirdParty->id,
        'amount' => 15000.50,
        'status' => 'submitted',
        'message' => 'Devis joint à la plateforme',
    ]);

    expect($offer->id)->not->toBeNull()
        ->and($offer->amount)->toEqual(15000.50)
        ->and($consultation->offers()->count())->toBe(1);
});
