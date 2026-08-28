<?php

use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

it('expires document if date is past', function () {
    $thirdParty = ThirdParty::factory()->create();

    $document = ThirdPartyDocument::create([
        'third_party_id' => $thirdParty->id,
        'type' => ThirdPartyDocumentType::KBIS,
        'expiration_date' => Carbon::now()->subDay(),
        'status' => ThirdPartyDocumentStatus::VALID,
    ]);

    Artisan::call('app:check-third-party-documents');

    $document->refresh();

    expect($document->status)->toBe(ThirdPartyDocumentStatus::EXPIRED);
});

it('keeps document valid if date is future', function () {
    $thirdParty = ThirdParty::factory()->create();

    $document = ThirdPartyDocument::create([
        'third_party_id' => $thirdParty->id,
        'type' => ThirdPartyDocumentType::KBIS,
        'expiration_date' => Carbon::now()->addDays(5),
        'status' => ThirdPartyDocumentStatus::VALID,
    ]);

    Artisan::call('app:check-third-party-documents');

    $document->refresh();

    expect($document->status)->toBe(ThirdPartyDocumentStatus::VALID);
});
