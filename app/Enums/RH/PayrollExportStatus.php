<?php

namespace App\Enums\RH;

enum PayrollExportStatus: string
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case EXPORTED = 'exported';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::VALIDATED => 'Validé',
            self::EXPORTED => 'Exporté',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'warning',
            self::VALIDATED => 'success',
            self::EXPORTED => 'gray',
        };
    }
}
