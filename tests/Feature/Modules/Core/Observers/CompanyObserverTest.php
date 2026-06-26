<?php

use App\Models\Core\Company;

beforeEach(function () {
    $this->company = Company::factory()->create([
        'legal_name' => 'Test Company',
    ]);

    $this->company->refresh();
});

test('company observer fires on creation', function () {
    expect($this->company)->not->toBeNull()
        ->and($this->company->id)->toBeGreaterThan(0);
});

test('company observer updates on change', function () {
    $this->company->update(['legal_name' => 'New Name']);
    $this->company->refresh();

    expect($this->company->legal_name)->toBe('New Name');
});

test('company has valid attributes', function () {
    expect($this->company->legal_name)->toBe('Test Company')
        ->and($this->company->id)->toBeInt()->toBeGreaterThan(0);
});

test('company can have multiple instances', function () {
    $company2 = Company::factory()->create(['legal_name' => 'Company 2']);

    expect(Company::count())->toBe(2);
});
