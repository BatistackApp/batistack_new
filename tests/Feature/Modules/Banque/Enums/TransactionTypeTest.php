<?php

use App\Enums\Banque\TransactionType;

it('has correct labels for transaction types', function () {
    expect(TransactionType::CREDIT->getLabel())->toBe('Crédit')
        ->and(TransactionType::DEBIT->getLabel())->toBe('Débit');
});

it('has correct colors for transaction types', function () {
    expect(TransactionType::CREDIT->getColor())->toBe('success')
        ->and(TransactionType::DEBIT->getColor())->toBe('danger');
});
