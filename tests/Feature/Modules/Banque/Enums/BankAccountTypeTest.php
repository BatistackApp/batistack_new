<?php

use App\Enums\Banque\BankAccountType;

it('has correct labels for bank account types', function () {
    expect(BankAccountType::CHECKING->getLabel())->toBe('Compte Courant')
        ->and(BankAccountType::SAVINGS->getLabel())->toBe('Compte Épargne')
        ->and(BankAccountType::CREDIT_CARD->getLabel())->toBe('Carte de Crédit');
});

it('has correct colors for bank account types', function () {
    expect(BankAccountType::CHECKING->getColor())->toBe('primary')
        ->and(BankAccountType::SAVINGS->getColor())->toBe('success')
        ->and(BankAccountType::CREDIT_CARD->getColor())->toBe('warning');
});
