<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JournalType: string implements HasColor, HasLabel
{
    case ACHATS = 'achats';
    case VENTES = 'ventes';
    case BANQUE = 'banque';
    case CAISSE = 'caisse';
    case OD = 'od';
    case ANO = 'ano'; // A-nouveaux

    public function getLabel(): string
    {
        return match ($this) {
            self::ACHATS => 'Achats',
            self::VENTES => 'Ventes',
            self::BANQUE => 'Banque',
            self::CAISSE => 'Caisse',
            self::OD => 'OD (Operations Diverses)',
            self::ANO => 'A-nouveaux',
        };
    }

    public function getCode(): string
    {
        return match ($this) {
            self::ACHATS => 'ACH',
            self::VENTES => 'VEN',
            self::BANQUE => 'BQ',
            self::CAISSE => 'CAI',
            self::OD => 'OD',
            self::ANO => 'ANO',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACHATS => 'warning',
            self::VENTES => 'success',
            self::BANQUE => 'info',
            self::CAISSE => 'gray',
            self::OD => 'primary',
            self::ANO => 'danger',
        };
    }
}
