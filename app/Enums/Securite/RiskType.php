<?php

namespace App\Enums\Securite;

use Filament\Support\Contracts\HasLabel;

/**
 * Types de risques déduits des fiches produits (dangers CLP).
 */
enum RiskType: string implements HasLabel
{
    case EXPLOSION = 'explosion';
    case INCENDIE = 'incendie';
    case INTOXICATION = 'intoxication';
    case CORROSION = 'corrosion';
    case SANTE_LONG_TERME = 'sante_long_terme';
    case ALLERGIE = 'allergie';
    case PROJECTION = 'projection';
    case POLLUTION = 'pollution';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EXPLOSION => 'Risque d\'explosion',
            self::INCENDIE => 'Risque d\'incendie',
            self::INTOXICATION => 'Risque d\'intoxication / nocif',
            self::CORROSION => 'Risque de brûlure / corrosion',
            self::SANTE_LONG_TERME => 'Risque pour la santé à long terme',
            self::ALLERGIE => 'Risque d\'allergie / sensibilisation',
            self::PROJECTION => 'Risque de projection',
            self::POLLUTION => 'Risque de pollution de l\'environnement',
        };
    }

    /**
     * Équipements de Protection Individuelle recommandés.
     */
    public function getEpi(): array
    {
        return match ($this) {
            self::EXPLOSION => ['Interdiction de tout objet ou outil produisant des étincelles'],
            self::INCENDIE => ['Vêtements de travail non inflammables'],
            self::INTOXICATION => ['Gants de protection', 'Lunettes de protection', 'Protection respiratoire (masque adapté)'],
            self::CORROSION => ['Gants de protection chimique', 'Lunettes étanches', 'Tablier / vêtement de protection'],
            self::SANTE_LONG_TERME => ['Protection respiratoire adaptée', 'Vêtement de travail spécifique'],
            self::ALLERGIE => ['Gants de protection', 'Vêtements couvrants'],
            self::PROJECTION => ['Lunettes de protection', 'Casque de protection'],
            self::POLLUTION => ['Gants de protection', 'Combinaison étanche'],
        };
    }

    /**
     * Mesures de protection collective / organisationnelles.
     */
    public function getCollective(): array
    {
        return match ($this) {
            self::EXPLOSION => ['Travaux par points chauds soumis à permis de feu', 'Zone dangereuse balisée et signalée'],
            self::INCENDIE => ['Extincteurs à proximité du poste', 'Stockage des produits inflammables séparé et ventilé', 'Interdiction de fumer'],
            self::INTOXICATION => ['Ventilation / aération du poste de travail', 'Interdiction de manger et de boire sur le poste'],
            self::CORROSION => ['Douche de sécurité et rince-œil à proximité', 'Bac de rétention anti-écoulement'],
            self::SANTE_LONG_TERME => ['Contrôle de l\'exposition professionnelle (VLEP)', 'Surveillance médicale renforcée'],
            self::ALLERGIE => ['Limitation de la durée d\'exposition', 'Information et formation du personnel'],
            self::PROJECTION => ['Éloignement du personnel non concerné', 'Zone de tir / de travail balisée'],
            self::POLLUTION => ['Kit anti-pollution sur site', 'Bac de rétention', 'Respect de la réglementation environnementale'],
        };
    }
}
