<?php

use App\Models\RH\ExpenseItem;
use App\Services\RH\ExpenseValidationService;

it('validates expense items against policies', function () {
    $service = new ExpenseValidationService;

    $item1 = new ExpenseItem([
        'category' => 'Repas',
        'amount_ttc' => 15.00,
        'amount_ht' => 12.50,
        'vat_amount' => 2.50,
        'date' => now()->subDay(),
    ]);

    $result1 = $service->validateItem($item1);
    expect($result1['is_valid'])->toBeTrue();

    $item2 = new ExpenseItem([
        'category' => 'Repas',
        'amount_ttc' => 25.00, // Above limit of 20.20
        'amount_ht' => 20.83,
        'vat_amount' => 4.17,
        'date' => now()->subDay(),
    ]);

    $result2 = $service->validateItem($item2);
    expect($result2['is_valid'])->toBeFalse()
        ->and($result2['reason'])->toContain('dépasse le plafond autorisé');
});

it('detects vat inconsistencies', function () {
    $service = new ExpenseValidationService;

    $item = new ExpenseItem([
        'category' => 'Péage',
        'amount_ttc' => 10.00,
        'amount_ht' => 5.00, // Invalid HT + VAT != TTC
        'vat_amount' => 2.00,
        'date' => now()->subDay(),
    ]);

    $result = $service->validateItem($item);
    expect($result['is_valid'])->toBeFalse()
        ->and($result['reason'])->toContain('Incohérence détectée');
});

it('prevents future dates', function () {
    $service = new ExpenseValidationService;

    $item = new ExpenseItem([
        'category' => 'Hébergement',
        'amount_ttc' => 50.00,
        'date' => now()->addDays(5),
    ]);

    $result = $service->validateItem($item);
    expect($result['is_valid'])->toBeFalse()
        ->and($result['reason'])->toContain('futur');
});
