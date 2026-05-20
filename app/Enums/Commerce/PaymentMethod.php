<?php

namespace App\Enums\Commerce;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum PaymentMethod: string implements HasIcon, HasLabel
{
    case BANK_TRANSFER = 'bank_transfer';
    case CHECK = 'check';
    case CREDIT_CARD = 'credit_card';
    case CASH = 'cash';
    case DIRECT_DEBIT = 'direct_debit';
    case LCR = 'lcr'; // Lettre de Change Relevé (Très utilisé en B2B BTP)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Virement bancaire',
            self::CHECK => 'Chèque',
            self::CREDIT_CARD => 'Carte Bancaire',
            self::CASH => 'Espèces',
            self::DIRECT_DEBIT => 'Prélèvement',
            self::LCR => 'Traite / LCR',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::BANK_TRANSFER => Phosphor::Bank,
            self::CHECK => Phosphor::Money,
            self::CREDIT_CARD => Phosphor::CreditCard,
            self::CASH => Phosphor::Coins,
            self::DIRECT_DEBIT => Phosphor::ArrowsLeftRight,
            self::LCR => Phosphor::FileText,
        };
    }
}
