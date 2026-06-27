<?php

use App\Models\Core\Unit;

beforeEach(function () {
    $this->unit = Unit::factory()->create([
        'name' => 'Mètre',
        'symbol' => 'm',
    ]);
});

test('unit observer creates reference', function () {
    expect($this->unit)
        ->not->toBeNull()
        ->and($this->unit->symbol)->toBe('m');
});

test('unit has valid name', function () {
    expect($this->unit->name)->not->toBeEmpty()
        ->and($this->unit->name)->toBe('Mètre');
});

test('unit abbreviation is stored', function () {
    $stored = Unit::find($this->unit->id);

    expect($stored->symbol)->toBe('m');
});

test('units are countable', function () {
    Unit::factory()->create(['symbol' => 'km']);

    expect(Unit::count())->toBe(2);
});
