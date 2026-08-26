<?php

namespace App\Enums\Chantiers;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum DoeDocumentCategory: string implements HasLabel
{
    case PLAN = 'plan';
    case NOTICE = 'notice';
    case FICHE_TECHNIQUE = 'fiche_technique';
    case CONFORMITE = 'conformite';
    case AUTRE = 'autre';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::PLAN => 'Plan',
            self::NOTICE => 'Notice',
            self::FICHE_TECHNIQUE => 'Fiche Technique',
            self::CONFORMITE => 'Conformité',
            self::AUTRE => 'Autre',
            default => null,
        };
    }
}
