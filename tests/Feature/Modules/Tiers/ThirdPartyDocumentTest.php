<?php

use App\Models\Tiers\ThirdPartyDocument;
use App\Models\Tiers\ThirdParty;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

it('expires document if date is past', function () {
    $thirdParty = ThirdParty::factory()->create();

    $document = ThirdPartyDocument::create([
        'third_party_id' => $thirdParty->id,
        'type' => 'kbis',
        'expiration_date' => Carbon::now()->subDay(),
        'status' => 'valid',
    ]);

    Artisan::call('app:check-third-party-documents');

    $document->refresh();

    expect($document->status)->toBe('expired');
});

it('keeps document valid if date is future', function () {
    $thirdParty = ThirdParty::factory()->create();

    $document = ThirdPartyDocument::create([
        'third_party_id' => $thirdParty->id,
        'type' => 'kbis',
        'expiration_date' => Carbon::now()->addDays(5),
        'status' => 'valid',
    ]);

    Artisan::call('app:check-third-party-documents');

    $document->refresh();

    expect($document->status)->toBe('valid');
});
