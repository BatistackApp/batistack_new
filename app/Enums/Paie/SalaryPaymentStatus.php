<?php

namespace App\Enums\Paie;

enum SalaryPaymentStatus: string
{
    case PENDING = 'pending';
    case AWAITING_VALIDATION = 'awaiting_validation';
    case PROCESSING = 'processing';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELED = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::AWAITING_VALIDATION => 'En attente de validation',
            self::PROCESSING => 'En traitement',
            self::SUCCEEDED => 'Réussi',
            self::FAILED => 'Échec',
            self::CANCELED => 'Annulé',
        };
    }
}
