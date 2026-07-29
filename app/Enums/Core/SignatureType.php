<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum SignatureType: string implements HasIcon, HasLabel
{
    case AUTOGRAPH = 'autograph'; // Dessin manuel (Filament Autograph)
    case OTP = 'otp';             // Code de validation par mail/SMS
    case CLICK = 'click';         // Simple clic d'approbation (interne)

    case EIDAS = 'eidas';         // Signature certifiée eIDAS (Yousign, Docuseal)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::AUTOGRAPH => 'Signature manuscrite',
            self::OTP => 'Validation OTP',
            self::CLICK => 'Approbation simple',
            self::EIDAS => 'Signature certifiée (eIDAS)',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::AUTOGRAPH => Phosphor::Signature,
            self::OTP => Phosphor::ShieldCheck,
            self::CLICK => Phosphor::CursorClick,
            self::EIDAS => Phosphor::SealCheck,
        };
    }
}
