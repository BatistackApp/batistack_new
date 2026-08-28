<?php

namespace Tests\Feature\Modules\Tiers\Models;

use App\Models\Chantiers\Chantier;
use App\Models\Tiers\Consultation;
use App\Models\Tiers\ConsultationOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Consultation - Relations', function () {
    test('appartient à un chantier', function () {
        $chantier = Chantier::factory()->create();
        $consultation = Consultation::factory()->create(['chantier_id' => $chantier->id]);

        expect($consultation->chantier->id)->toBe($chantier->id);
    });

    test('peut avoir plusieurs offres', function () {
        $consultation = Consultation::factory()->create();

        ConsultationOffer::factory(3)->create(['consultation_id' => $consultation->id]);

        expect($consultation->offers->count())->toBe(3);
    });
});
