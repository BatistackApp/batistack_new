<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasLabel;

/**
 * Pictogrammes de danger CLP (règlement 1272/2008).
 */
enum GhsPictogram: string implements HasLabel
{
    case GHS01 = 'ghs01'; // Explosif
    case GHS02 = 'ghs02'; // Inflammable
    case GHS03 = 'ghs03'; // Comburant
    case GHS04 = 'ghs04'; // Gaz sous pression
    case GHS05 = 'ghs05'; // Corrosif
    case GHS06 = 'ghs06'; // Toxique
    case GHS07 = 'ghs07'; // Nocif / Irritant
    case GHS08 = 'ghs08'; // Danger pour la santé
    case GHS09 = 'ghs09'; // Dangereux pour l'environnement

    public function getLabel(): ?string
    {
        return match ($this) {
            self::GHS01 => 'Explosif (GHS01)',
            self::GHS02 => 'Inflammable (GHS02)',
            self::GHS03 => 'Comburant (GHS03)',
            self::GHS04 => 'Gaz sous pression (GHS04)',
            self::GHS05 => 'Corrosif (GHS05)',
            self::GHS06 => 'Toxique (GHS06)',
            self::GHS07 => 'Nocif / Irritant (GHS07)',
            self::GHS08 => 'Danger pour la santé (GHS08)',
            self::GHS09 => 'Dangereux pour l\'environnement (GHS09)',
        };
    }

    /**
     * Représentation graphique (emoji) pour les documents imprimés.
     */
    public function getGlyph(): string
    {
        return match ($this) {
            self::GHS01 => '💥',
            self::GHS02 => '🔥',
            self::GHS03 => '🧯',
            self::GHS04 => '🧴',
            self::GHS05 => '🧪',
            self::GHS06 => '☠️',
            self::GHS07 => '❗',
            self::GHS08 => '🫁',
            self::GHS09 => '🐟',
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::GHS01, self::GHS02, self::GHS03, self::GHS06, self::GHS05 => 'danger',
            self::GHS08, self::GHS04 => 'warning',
            self::GHS07 => 'info',
            self::GHS09 => 'success',
        };
    }
}
