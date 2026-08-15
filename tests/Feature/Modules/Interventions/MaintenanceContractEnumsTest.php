<?php

use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;

it('maps maintenance contract statuses to labels', function () {
    expect(MaintenanceContractStatus::ACTIVE->getLabel())->toBe('Actif')
        ->and(MaintenanceContractStatus::PAUSED->getLabel())->toBe('En pause')
        ->and(MaintenanceContractStatus::COMPLETED->getLabel())->toBe('Terminé')
        ->and(MaintenanceContractStatus::CANCELLED->getLabel())->toBe('Annulé');
});

it('maps maintenance contract statuses to colors', function () {
    expect(MaintenanceContractStatus::ACTIVE->getColor())->toBe('success')
        ->and(MaintenanceContractStatus::PAUSED->getColor())->toBe('warning')
        ->and(MaintenanceContractStatus::COMPLETED->getColor())->toBe('primary')
        ->and(MaintenanceContractStatus::CANCELLED->getColor())->toBe('gray');
});

it('maps maintenance contract statuses to icons', function () {
    expect(MaintenanceContractStatus::ACTIVE->getIcon())->toBe('heroicon-o-check-circle')
        ->and(MaintenanceContractStatus::PAUSED->getIcon())->toBe('heroicon-o-pause-circle')
        ->and(MaintenanceContractStatus::COMPLETED->getIcon())->toBe('heroicon-o-flag')
        ->and(MaintenanceContractStatus::CANCELLED->getIcon())->toBe('heroicon-o-x-circle');
});

it('exposes the raw values of maintenance contract statuses', function () {
    expect(MaintenanceContractStatus::ACTIVE->value)->toBe('active')
        ->and(MaintenanceContractStatus::CANCELLED->value)->toBe('cancelled');
});

it('maps maintenance frequencies to labels', function () {
    expect(MaintenanceContractFrequency::MONTHLY->getLabel())->toBe('Mensuel')
        ->and(MaintenanceContractFrequency::QUARTERLY->getLabel())->toBe('Trimestriel')
        ->and(MaintenanceContractFrequency::SEMI_ANNUAL->getLabel())->toBe('Semestriel')
        ->and(MaintenanceContractFrequency::ANNUAL->getLabel())->toBe('Annuel');
});

it('maps maintenance frequencies to colors', function () {
    expect(MaintenanceContractFrequency::MONTHLY->getColor())->toBe('info')
        ->and(MaintenanceContractFrequency::QUARTERLY->getColor())->toBe('primary')
        ->and(MaintenanceContractFrequency::SEMI_ANNUAL->getColor())->toBe('warning')
        ->and(MaintenanceContractFrequency::ANNUAL->getColor())->toBe('success');
});

it('maps maintenance frequencies to icons', function () {
    expect(MaintenanceContractFrequency::MONTHLY->getIcon())->toBe('heroicon-o-calendar')
        ->and(MaintenanceContractFrequency::QUARTERLY->getIcon())->toBe('heroicon-o-calendar-days')
        ->and(MaintenanceContractFrequency::SEMI_ANNUAL->getIcon())->toBe('heroicon-o-clock')
        ->and(MaintenanceContractFrequency::ANNUAL->getIcon())->toBe('heroicon-o-calendar');
});

it('exposes the raw values of maintenance frequencies', function () {
    expect(MaintenanceContractFrequency::SEMI_ANNUAL->value)->toBe('semi_annual')
        ->and(MaintenanceContractFrequency::ANNUAL->value)->toBe('annual');
});
