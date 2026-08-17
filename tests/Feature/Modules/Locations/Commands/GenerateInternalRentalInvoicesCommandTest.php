<?php

use App\Enums\Locations\RentalBillingPeriod;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Locations\InternalRentalInvoice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('generates internal rental invoices via the command and reports the count', function () {
    $category = AssetCategory::factory()->create();
    $chantier = Chantier::factory()->create();
    $asset = FixedAsset::factory()->create([
        'asset_category_id' => $category->id,
        'chantier_id' => $chantier->id,
        'daily_rate' => 100,
        'internal_rental_period' => RentalBillingPeriod::MONTHLY,
    ]);

    // L'observer a déjà facturé à la création de l'actif.
    expect(InternalRentalInvoice::count())->toBe(1);

    Carbon::setTestNow(Carbon::create(2026, 9, 15));
    $exitCode = Artisan::call('locations:bill-internal-rentals', [
        '--reference' => '2026-09-15',
    ]);
    Carbon::setTestNow(null);

    expect($exitCode)->toBe(0)
        ->and(InternalRentalInvoice::count())->toBe(2)
        ->and(Artisan::output())->toContain('1 factures internes générées');
});

it('logs when no internal rental invoices are generated', function () {
    $exitCode = Artisan::call('locations:bill-internal-rentals');

    expect($exitCode)->toBe(0)
        ->and(Artisan::output())->toContain('0 factures internes générées');
});
