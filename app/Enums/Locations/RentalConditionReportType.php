<?php

namespace App\Enums\Locations;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RentalConditionReportType: string implements HasColor, HasLabel
{
    case RECEPTION = 'reception';
    case RESTITUTION = 'restitution';

    public function getLabel(): string
    {
        return match ($this) {
            self::RECEPTION => 'Réception',
            self::RESTITUTION => 'Restitution',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::RECEPTION => 'success',
            self::RESTITUTION => 'danger',
        };
    }
}
