<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ContractType: string implements HasColor, HasDescription, HasLabel
{
    case CDI = 'cdi';
    case CDD = 'cdd';
    case INTERIM = 'interim';
    case APPRENTICE = 'apprentice';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CDI => 'CDI',
            self::CDD => 'CDD',
            self::INTERIM => 'Intérimaire',
            self::APPRENTICE => 'Apprentissage / Alternance',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CDI => 'success',
            self::CDD => 'warning',
            self::INTERIM => 'info',
            self::APPRENTICE => 'gray',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::CDI => 'Contrat à Durée Indéterminée',
            self::CDD => 'Contrat à Durée Déterminée',
            self::INTERIM => "Contrat d'Intérim (Sous convention d'entreprise)",
            self::APPRENTICE => 'Contrat d\'Apprentissage / Alternance',
        };
    }
}
