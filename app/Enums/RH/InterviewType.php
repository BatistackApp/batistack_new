<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InterviewType: string implements HasColor, HasLabel
{
    case ANNUEL = 'annuel';
    case PROFESSIONNEL = 'professionnel';
    case RECADRAGE = 'recadrage';
    case AUTRE = 'autre';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ANNUEL => 'Annuel',
            self::PROFESSIONNEL => 'Professionnel',
            self::RECADRAGE => 'Recadrage',
            self::AUTRE => 'Autre',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ANNUEL => 'primary',
            self::PROFESSIONNEL => 'info',
            self::RECADRAGE => 'danger',
            self::AUTRE => 'gray',
        };
    }
}
