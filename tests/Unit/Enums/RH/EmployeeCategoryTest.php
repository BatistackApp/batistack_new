<?php

use App\Enums\RH\EmployeeCategory;

it('returns correct label for ouvrier', function () {
    expect(EmployeeCategory::OUVRIER->getLabel())->toBe('Ouvrier');
});

it('returns correct label for etam', function () {
    expect(EmployeeCategory::ETAM->getLabel())->toBe('ETAM');
});

it('returns correct label for cadre', function () {
    expect(EmployeeCategory::CADRE->getLabel())->toBe('Cadre');
});
