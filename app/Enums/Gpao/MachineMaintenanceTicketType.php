<?php

namespace App\Enums\Gpao;

use Filament\Support\Contracts\HasLabel;

enum MachineMaintenanceTicketType: string implements HasLabel
{
    case PREVENTIVE = 'preventive';
    case CURATIVE = 'curative';
    case CORRECTIVE = 'corrective';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PREVENTIVE => 'Préventif',
            self::CURATIVE => 'Curatif',
            self::CORRECTIVE => 'Correctif',
        };
    }
}
