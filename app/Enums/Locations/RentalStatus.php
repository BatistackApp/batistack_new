<?php

namespace App\Enums\Locations;

enum RentalStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case OVERDUE = 'overdue';
    case TERMINATED = 'terminated';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::ACTIVE => 'Actif',
            self::SUSPENDED => 'Suspendu',
            self::OVERDUE => 'En dépassement',
            self::TERMINATED => 'Terminé',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::ACTIVE => 'success',
            self::SUSPENDED => 'warning',
            self::OVERDUE => 'danger',
            self::TERMINATED => 'danger',
        };
    }
}
