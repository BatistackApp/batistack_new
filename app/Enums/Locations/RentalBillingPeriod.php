<?php

namespace App\Enums\Locations;

enum RentalBillingPeriod: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    public function getLabel(): string
    {
        return match ($this) {
            self::DAILY => 'Journalier',
            self::WEEKLY => 'Hebdomadaire',
            self::MONTHLY => 'Mensuel',
            self::YEARLY => 'Annuel',
        };
    }
}
