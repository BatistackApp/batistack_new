<?php

use App\Enums\Paie\SalaryPaymentStatus;

it('maps every status to a label', function () {
    expect(SalaryPaymentStatus::PENDING->getLabel())->toBe('En attente')
        ->and(SalaryPaymentStatus::AWAITING_VALIDATION->getLabel())->toBe('En attente de validation')
        ->and(SalaryPaymentStatus::PROCESSING->getLabel())->toBe('En traitement')
        ->and(SalaryPaymentStatus::SUCCEEDED->getLabel())->toBe('Réussi')
        ->and(SalaryPaymentStatus::FAILED->getLabel())->toBe('Échec')
        ->and(SalaryPaymentStatus::CANCELED->getLabel())->toBe('Annulé');
});
