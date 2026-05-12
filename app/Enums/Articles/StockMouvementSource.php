<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasLabel;

enum StockMouvementSource: string implements HasLabel
{
    case PURCHASE = 'purchase';   // Commande fournisseur
    case SITE = 'site';           // Consommation Chantier
    case INVENTORY = 'inventory'; // Inventaire physique
    case INTERNAL = 'internal';   // Usage interne / Casse
    case RETURN = 'return';       // Retour de chantier

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PURCHASE => 'Commande Fournisseur',
            self::SITE => 'Consommation Chantier',
            self::INVENTORY => 'Régularisation Inventaire',
            self::INTERNAL => 'Usage Interne / Perte',
            self::RETURN => 'Retour Chantier',
        };
    }
}
