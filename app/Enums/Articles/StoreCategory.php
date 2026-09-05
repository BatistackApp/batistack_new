<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum StoreCategory: string implements HasColor, HasIcon, HasLabel
{
    case EPI = 'epi';
    case FIXATION = 'fixation';
    case ABRASIF = 'abrasif';
    case LIANT = 'liant';
    case OUTILLAGE = 'outillage';
    case LECTURE_ECRITURE = 'lecture_ecriture';
    case NETWORK = 'network';
    case SECURITE = 'securite';
    case AUTRE = 'autre';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EPI => 'EPI',
            self::FIXATION => 'Fixation',
            self::ABRASIF => 'Abrasif',
            self::LIANT => 'Liant',
            self::OUTILLAGE => 'Outillage petit',
            self::LECTURE_ECRITURE => 'Lecture / Écriture',
            self::NETWORK => 'Network / Câblage',
            self::SECURITE => 'Sécurité',
            self::AUTRE => 'Autre',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EPI => 'warning',
            self::FIXATION => 'primary',
            self::ABRASIF => 'danger',
            self::LIANT => 'gray',
            self::OUTILLAGE => 'success',
            self::LECTURE_ECRITURE => 'info',
            self::NETWORK => 'info',
            self::SECURITE => 'danger',
            self::AUTRE => 'gray',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::EPI => Phosphor::ShieldCheck,
            self::FIXATION => Phosphor::Nail,
            self::ABRASIF => Phosphor::CircleDashed,
            self::LIANT => Phosphor::PaintRoller,
            self::OUTILLAGE => Phosphor::Hammer,
            self::LECTURE_ECRITURE => Phosphor::PencilSimple,
            self::NETWORK => Phosphor::Network,
            self::SECURITE => Phosphor::Shield,
            self::AUTRE => Phosphor::DotsSixVertical,
        };
    }
}
