<?php

namespace App\Enums\Paie;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DsnSubmissionStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case EXPORTED = 'exported';
    case SUBMITTED = 'submitted';
    case PARTIAL = 'partial';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::EXPORTED => 'Exportée',
            self::SUBMITTED => 'Soumise',
            self::PARTIAL => 'Partielle',
            self::ACCEPTED => 'Acceptée',
            self::REJECTED => 'Rejetée',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::EXPORTED => 'warning',
            self::SUBMITTED => 'info',
            self::PARTIAL => 'warning',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
