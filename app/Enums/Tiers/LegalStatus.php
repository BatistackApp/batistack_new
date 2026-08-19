<?php

namespace App\Enums\Tiers;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum LegalStatus: string implements HasColor, HasIcon, HasLabel
{
    case SAIN = 'sain';
    case SAUVEGARDE = 'sauvegarde';
    case REDRESSEMENT_JUDICIAIRE = 'redressement_judiciaire';
    case LIQUIDATION_JUDICIAIRE = 'liquidation_judiciaire';
    case CESSATION = 'cessation';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SAIN => 'Sain',
            self::SAUVEGARDE => 'Sauvegarde',
            self::REDRESSEMENT_JUDICIAIRE => 'Redressement judiciaire',
            self::LIQUIDATION_JUDICIAIRE => 'Liquidation judiciaire',
            self::CESSATION => 'Cessation',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SAIN => 'success',
            self::SAUVEGARDE => 'warning',
            self::REDRESSEMENT_JUDICIAIRE, self::LIQUIDATION_JUDICIAIRE, self::CESSATION => 'danger',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::SAIN => Phosphor::CheckCircle,
            self::SAUVEGARDE => Phosphor::Warning,
            self::REDRESSEMENT_JUDICIAIRE, self::LIQUIDATION_JUDICIAIRE, self::CESSATION => Phosphor::XCircle,
        };
    }
}