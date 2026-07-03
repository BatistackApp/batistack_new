<?php

namespace App\Enums\Flottes;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

enum FleetExpenseType: string implements HasLabel
{
    case PEAGE = 'peage';
    case PARKING = 'parking';
    case WASH = 'lavage';
    case OTHER = 'autre';

    public function getLabel(): string|Htmlable|null
    {
        return Str::ucfirst($this->value);
    }
}
