<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can attach an invoice document media to a subcontractor situation', function () {
    Storage::fake('public');

    $thirdParty = ThirdParty::factory()->create();
    $chantier = Chantier::factory()->create();

    /** @var SubcontractorSituation $situation */
    $situation = SubcontractorSituation::factory()->create([
        'subcontractor_id' => $thirdParty->id,
        'chantier_id' => $chantier->id,
        'reference' => 'SIT-001',
        'progress_percentage' => 50,
        'total_ht' => 1000.00,
    ]);

    $file = UploadedFile::fake()->createWithContent('facture.pdf', 'pdf content here');

    $situation->addMedia($file)->toMediaCollection('invoice_document');

    expect($situation->hasMedia('invoice_document'))->toBeTrue();

    $media = $situation->getFirstMedia('invoice_document');
    expect($media->file_name)->toBe('facture.pdf');
});
