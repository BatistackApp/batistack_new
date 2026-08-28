<?php

namespace App\Enums\RH;

enum ExpenseReportStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
    case PAID = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumise',
            self::VALIDATED => 'Validée',
            self::REJECTED => 'Rejetée',
            self::PAID => 'Payée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'warning',
            self::VALIDATED => 'success',
            self::REJECTED => 'danger',
            self::PAID => 'success',
        };
    }
}
