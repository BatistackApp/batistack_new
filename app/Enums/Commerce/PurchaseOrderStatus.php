<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderStatus: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case SENT = 'sent';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::APPROVED => 'Approuvé (Conducteur)',
            self::SENT => 'Envoyé fournisseur',
            self::RECEIVED => 'Livré / Réceptionné',
            self::CANCELLED => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::APPROVED => 'primary',
            self::SENT => 'warning',
            self::RECEIVED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
