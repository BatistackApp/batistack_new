<?php

use App\Models\Core\VatRate;

beforeEach(function () {
    $this->vat = VatRate::factory()->create([
        'rate' => 20.0,
    ]);
});

test('vat rate observer validates percentage', function () {
    expect((float) $this->vat->rate)->toBe(20.0)
        ->and($this->vat->rate)->toBeGreaterThanOrEqual(0)
        ->and($this->vat->rate)->toBeLessThanOrEqual(100);
});

test('vat rate can be updated', function () {
    $this->vat->update(['rate' => 21.0]);

    expect((float) VatRate::find($this->vat->id)->rate)->toBe(21.0);
});

test('multiple vat rates can exist', function () {
    VatRate::factory()->create(['rate' => 5.5]);

    expect(VatRate::count())->toBe(2);
});

test('vat rate is numeric', function () {
    expect($this->vat->rate)->toBeNumeric();
});
