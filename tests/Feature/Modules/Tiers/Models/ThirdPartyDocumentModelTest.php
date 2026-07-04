<?php

namespace Tests\Feature\Modules\Tiers\Models;

use App\Models\Tiers\ThirdPartyDocument;
use App\Models\Tiers\ThirdParty;
use App\Models\Core\Signature;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ThirdPartyDocument - Relations', function () {
    test('appartient à un tiers', function () {
        $thirdParty = ThirdParty::factory()->create();
        $document = ThirdPartyDocument::factory()->create(['third_party_id' => $thirdParty->id]);

        expect($document->thirdParty->id)->toBe($thirdParty->id);
    });

    test('peut avoir des signatures polymorphiques', function () {
        $document = ThirdPartyDocument::factory()->create();
        $user = \App\Models\User::factory()->create();
        
        $signature = new Signature([
            'user_id' => $user->id,
            'signed_at' => now(),
            'ip_address' => '127.0.0.1',
            'checksum' => 'test-checksum'
        ]);
        
        $document->signatures()->save($signature);

        expect($document->signatures->count())->toBe(1)
            ->and($document->signatures->first()->id)->toBe($signature->id);
    });
});
