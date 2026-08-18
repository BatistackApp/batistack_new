<?php

use App\Enums\Locations\InternalRentalInvoiceStatus;

it('has correct labels for internal rental invoice statuses', function () {
    expect(InternalRentalInvoiceStatus::DRAFT->getLabel())->toBe('Brouillon')
        ->and(InternalRentalInvoiceStatus::VALIDATED->getLabel())->toBe('Validée')
        ->and(InternalRentalInvoiceStatus::CANCELED->getLabel())->toBe('Annulée');
});

it('has correct colors for internal rental invoice statuses', function () {
    expect(InternalRentalInvoiceStatus::DRAFT->getColor())->toBe('gray')
        ->and(InternalRentalInvoiceStatus::VALIDATED->getColor())->toBe('success')
        ->and(InternalRentalInvoiceStatus::CANCELED->getColor())->toBe('danger');
});
