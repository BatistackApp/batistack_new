<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasLabel;

enum TimeEntryType: string implements HasLabel
{
    case NORMAL = 'normal';
    case OVERTIME_25 = 'overtime_25';
    case OVERTIME_50 = 'overtime_50';
    case NIGHT = 'night';
    case SUNDAY = 'sunday';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NORMAL => 'Heures Normales',
            self::OVERTIME_25 => 'Heures Sup. 25%',
            self::OVERTIME_50 => 'Heures Sup. 50%',
            self::NIGHT => 'Heures de Nuit',
            self::SUNDAY => 'Dimanche / Férié',
        };
    }
}
