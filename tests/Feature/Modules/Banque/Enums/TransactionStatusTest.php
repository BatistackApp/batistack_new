<?php

use App\Enums\Banque\TransactionStatus;

it('has correct labels for transaction statuses', function () {
    expect(TransactionStatus::PENDING->getLabel())->toBe('À Lettrer')
        ->and(TransactionStatus::RECONCILED->getLabel())->toBe('Lettrée')
        ->and(TransactionStatus::IGNORED->getLabel())->toBe('Ignorée');
});

it('has correct colors for transaction statuses', function () {
    expect(TransactionStatus::PENDING->getColor())->toBe('warning')
        ->and(TransactionStatus::RECONCILED->getColor())->toBe('success')
        ->and(TransactionStatus::IGNORED->getColor())->toBe('gray');
});
