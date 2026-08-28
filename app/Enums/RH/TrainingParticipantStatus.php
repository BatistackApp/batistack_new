<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TrainingParticipantStatus: string implements HasColor, HasLabel
{
    case INSCRIT = 'inscrit';
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case VALIDE = 'valide';
    case ECHOUE = 'echoue';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INSCRIT => 'Inscrit',
            self::PRESENT => 'Présent',
            self::ABSENT => 'Absent',
            self::VALIDE => 'Validé / Obtenu',
            self::ECHOUE => 'Échoué',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INSCRIT => 'gray',
            self::PRESENT => 'info',
            self::ABSENT => 'danger',
            self::VALIDE => 'success',
            self::ECHOUE => 'danger',
        };
    }
}
