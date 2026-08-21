<?php

use App\Enums\Locations\RentalStatus;

it('has correct labels for all rental statuses', function () {
    expect(RentalStatus::DRAFT->getLabel())->toBe('Brouillon')
        ->and(RentalStatus::ACTIVE->getLabel())->toBe('Actif')
        ->and(RentalStatus::SUSPENDED->getLabel())->toBe('Suspendu')
        ->and(RentalStatus::OVERDUE->getLabel())->toBe('En dépassement')
        ->and(RentalStatus::TERMINATED->getLabel())->toBe('Terminé');
});

it('has correct colors for all rental statuses', function () {
    expect(RentalStatus::DRAFT->getColor())->toBe('gray')
        ->and(RentalStatus::ACTIVE->getColor())->toBe('success')
        ->and(RentalStatus::SUSPENDED->getColor())->toBe('warning')
        ->and(RentalStatus::OVERDUE->getColor())->toBe('danger')
        ->and(RentalStatus::TERMINATED->getColor())->toBe('danger');
});
