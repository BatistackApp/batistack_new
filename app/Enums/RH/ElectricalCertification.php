<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasDescription;
use Illuminate\Contracts\Support\Htmlable;

enum ElectricalCertification: string implements HasDescription
{
    // Travaux non électriques
    case B0 = 'B0';
    case H0 = 'H0';
    case H0V = 'H0V';

    // Exécutants électriciens
    case B1 = 'B1';
    case B1V = 'B1V';
    case H1 = 'H1';
    case H1V = 'H1V';

    // Chargés de travaux (responsables d'équipe)
    case B2 = 'B2';
    case B2V = 'B2V';
    case H2 = 'H2';
    case H2V = 'H2V';

    // Consignation
    case BC = 'BC';
    case HC = 'HC';

    // Interventions BT (Basse Tension)
    case BR = 'BR';
    case BS = 'BS';

    // Opérations spécifiques
    case BE_Vérification = 'BE_Vérification';
    case BE_Mesurage = 'BE_Mesurage';
    case BE_Essai = 'BE_Essai';
    case BE_Manoeuvre = 'BE_Manoeuvre';
    case HE_Vérification = 'HE_Vérification';
    case HE_Mesurage = 'HE_Mesurage';
    case HE_Essai = 'HE_Essai';
    case HE_Manoeuvre = 'HE_Manoeuvre';

    // Photovoltaïque
    case BP = 'BP';

    public function getDescription(): string|Htmlable|null
    {
        return match($this) {
            self::B0 => 'B0 : Exécutant / Chargé de chantier d\'ordre non électrique (BT)',
            self::H0 => 'H0 : Exécutant / Chargé de chantier d\'ordre non électrique (HT)',
            self::H0V => 'H0V : Opérations d\'ordre non électrique au voisinage (HT)',

            self::B1 => 'B1 : Exécutant électricien (BT)',
            self::B1V => 'B1V : Exécutant électricien au voisinage (BT)',
            self::H1 => 'H1 : Exécutant électricien (HT)',
            self::H1V => 'H1V : Exécutant électricien au voisinage (HT)',

            self::B2 => 'B2 : Chargé de travaux d\'ordre électrique (BT)',
            self::B2V => 'B2V : Chargé de travaux au voisinage (BT)',
            self::H2 => 'H2 : Chargé de travaux d\'ordre électrique (HT)',
            self::H2V => 'H2V : Chargé de travaux au voisinage (HT)',

            self::BC => 'BC : Chargé de consignation (BT)',
            self::HC => 'HC : Chargé de consignation (HT)',

            self::BR => 'BR : Chargé d\'intervention BT générale (Dépannage / Connexion)',
            self::BS => 'BS : Chargé d\'intervention BT élémentaire (Remplacement / Raccordement simple)',

            self::BE_Vérification => 'BE Vérification : Opérations de vérification de conformité (BT)',
            self::BE_Mesurage => 'BE Mesurage : Prises de mesures électriques (BT)',
            self::BE_Essai => 'BE Essai : Chargé d\'essais en laboratoire ou plateforme (BT)',
            self::BE_Manoeuvre => 'BE Manœuvre : Manœuvres d\'exploitation de sauvegarde (BT)',

            self::HE_Vérification => 'HE Vérification : Opérations de vérification de conformité (HT)',
            self::HE_Mesurage => 'HE Mesurage : Prises de mesures électriques (HT)',
            self::HE_Essai => 'HE Essai : Chargé d\'essais en laboratoire ou plateforme (HT)',
            self::HE_Manoeuvre => 'HE Manœuvre : Manœuvres d\'exploitation de sauvegarde (HT)',

            self::BP => 'BP : Chargé d\'opérations élémentaires sur chaîne photovoltaïque',
        };
    }

    /**
     * Retourne le niveau de tension (Basse Tension ou Haute Tension)
     */
    public function voltageCategory(): string
    {
        return str_starts_with($this->value, 'H') ? 'Haute Tension (HT)' : 'Basse / Très Basse Tension (BT/TBT)';
    }

    public function validityPeriodInMonths(): int
    {
        return match ($this) {
            self::B0, self::BP, self::HE_Manoeuvre, self::HE_Essai, self::HE_Mesurage, self::HE_Vérification, self::BE_Manoeuvre, self::BE_Essai, self::BE_Mesurage, self::BE_Vérification, self::BS, self::BR, self::HC, self::BC, self::H2V, self::H2, self::B2V, self::B2, self::H1V, self::H1, self::B1V, self::B1, self::H0V, self::H0 => 999, // 5 ans pour les autres
        };
    }
}
