<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasDescription;
use Illuminate\Contracts\Support\Htmlable;

enum CertificationSymbol: string implements HasDescription
{
    case R482 = 'R482'; // Engins de chantier
    case R484 = 'R484'; // Ponts roulants et portiques
    case R485 = 'R485'; // Chariots de manutention à conducteur accompagnant (gerbeurs)
    case R486 = 'R486'; // PEMT (Nacelles)
    case R489 = 'R489'; // Chariots de manutention à conducteur porté (Clark, transpalettes portés)
    case R490 = 'R490'; // Grues de chargement

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

    case SST = 'SST';
    case PSC1 = 'PSC1';
    case PSE1 = 'PSE1';

    case VIP = 'vip';   // Visite d'Information et de Prévention
    case SIR = 'sir';   // Suivi Individuel Renforcé (Postes à risque)
    case REPRISE = 'reprise'; // Visite de reprise après arrêt
    case PRE_REPRISE = 'pre_reprise';

    case PERMIS = 'permis'; // Permis de conduire'

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::R482 => 'CACES R482 (Engins de chantier)',
            self::R484 => 'CACES R484 (Ponts roulants et portiques)',
            self::R485 => 'CACES R485 (Chariots gerbeurs à conducteur accompagnant)',
            self::R486 => 'CACES R486 (Nacelles / PEMT)',
            self::R489 => 'CACES R489 (Chariots élévateurs de manutention)',
            self::R490 => 'CACES R490 (Grues de chargement / auxiliaires de chargement)',
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
            self::SST => 'Sauveteur Secouriste du Travail (SST)',
            self::PSC1 => 'Prévention et Secours Civiques de niveau 1 (PSC1)',
            self::PSE1 => 'Premiers Secours en Équipe de niveau 1 (PSE1)',

            self::VIP => 'VIP (Standard)',
            self::SIR => 'SIR (Renforcé)',
            self::REPRISE => 'Visite de Reprise',
            self::PRE_REPRISE => 'Visite de Pré-reprise',

            self::PERMIS => 'Permis de conduire',
        };
    }

    /**
     * Convertit un CacesSymbol en CertificationSymbol.
     */
    public static function fromCacesSymbol(CacesSymbol $cacesSymbol): self
    {
        return match ($cacesSymbol) {
            CacesSymbol::R482 => self::R482,
            CacesSymbol::R484 => self::R484,
            CacesSymbol::R485 => self::R485,
            CacesSymbol::R486 => self::R486,
            CacesSymbol::R489 => self::R489,
            CacesSymbol::R490 => self::R490,
            CacesSymbol::PERMIS => self::PERMIS,
            // Ajoutez ici les mappings pour tous les autres cas de CacesSymbol
            default => throw new \ValueError("Conversion impossible de CacesSymbol::{$cacesSymbol->name} en CertificationSymbol"),
        };
    }

    /**
     * Convertit un ElectricalCertification en CertificationSymbol.
     */
    public static function fromElectricalCertification(ElectricalCertification $electricalCertification): self
    {
        return match ($electricalCertification) {
            // Exemple:
            // ElectricalCertification::B0 => self::ELECTRICAL_B0,
            // Ajoutez ici les mappings pour tous les cas de ElectricalCertification
            ElectricalCertification::B0 => self::B0,
            ElectricalCertification::H0 => self::H0,
            ElectricalCertification::H0V => self::H0V,
            ElectricalCertification::B1 => self::B1,
            ElectricalCertification::B1V => self::B1V,
            ElectricalCertification::H1 => self::H1,
            ElectricalCertification::H1V => self::H1V,
            ElectricalCertification::B2 => self::B2,
            ElectricalCertification::B2V => self::B2V,
            ElectricalCertification::H2 => self::H2,
            ElectricalCertification::H2V => self::H2V,
            ElectricalCertification::BC => self::BC,
            ElectricalCertification::HC => self::HC,
            ElectricalCertification::BR => self::BR,
            ElectricalCertification::BS => self::BS,
            ElectricalCertification::BE_Vérification => self::BE_Vérification,
            ElectricalCertification::BE_Mesurage => self::BE_Mesurage,
            ElectricalCertification::BE_Essai => self::BE_Essai,
            ElectricalCertification::BE_Manoeuvre => self::BE_Manoeuvre,
            ElectricalCertification::HE_Vérification => self::HE_Vérification,
            ElectricalCertification::HE_Mesurage => self::HE_Mesurage,
            ElectricalCertification::HE_Essai => self::HE_Essai,
            ElectricalCertification::HE_Manoeuvre => self::HE_Manoeuvre,
            ElectricalCertification::BP => self::BP,
            default => throw new \ValueError("Conversion impossible de ElectricalCertification::{$electricalCertification->name} en CertificationSymbol"),
        };
    }

    /**
     * Convertit un MedicalVisiteType en CertificationSymbol.
     */
    public static function fromMedicalVisiteType(MedicalVisiteType $medicalVisiteType): self
    {
        return match ($medicalVisiteType) {
            // Exemple:
            // MedicalVisiteType::APTITUDE => self::MEDICAL_APTITUDE,
            // Ajoutez ici les mappings pour tous les cas de MedicalVisiteType
            MedicalVisiteType::VIP => self::VIP,
            MedicalVisiteType::SIR => self::SIR,
            MedicalVisiteType::REPRISE => self::REPRISE,
            MedicalVisiteType::PRE_REPRISE => self::PRE_REPRISE,
            default => throw new \ValueError("Conversion impossible de MedicalVisiteType::{$medicalVisiteType->name} en CertificationSymbol"),
        };
    }

    /**
     * Convertit un SafetyAidSymbol en CertificationSymbol.
     */
    public static function fromSafetyAidSymbol(SafetyAidSymbol $safetyAidSymbol): self
    {
        return match ($safetyAidSymbol) {
            // Exemple:
            // SafetyAidSymbol::SST => self::SAFETY_SST,
            // Ajoutez ici les mappings pour tous les cas de SafetyAidSymbol
            SafetyAidSymbol::SST => self::SST,
            SafetyAidSymbol::PSC1 => self::PSC1,
            SafetyAidSymbol::PSE1 => self::PSE1,
            default => throw new \ValueError("Conversion impossible de SafetyAidSymbol::{$safetyAidSymbol->name} en CertificationSymbol"),
        };
    }

    /**
     * Retourne le niveau de tension (Basse Tension ou Haute Tension)
     */
    public function voltageCategory(): string
    {
        return str_starts_with($this->value, 'H') ? 'Haute Tension (HT)' : 'Basse / Très Basse Tension (BT/TBT)';
    }

    /**
     * Périodicité de recyclage recommandée/légale en mois
     */
    public function validityPeriodInMonths(): int
    {
        return match ($this) {
            self::SST => 24, // MAC tous les 2 ans réglementaire
            self::PSC1 => 36, // Conseillé
            self::PSE1 => 12, // Annuel obligatoire pour rester opérationnel
            self::R482 => 120, // 10 ans pour les engins de chantier
            self::R484, self::R485, self::R486, self::R489, self::R490, self::VIP => 60,
            self::B0, self::BP, self::HE_Manoeuvre, self::HE_Essai, self::HE_Mesurage, self::HE_Vérification, self::BE_Manoeuvre, self::BE_Essai, self::BE_Mesurage, self::BE_Vérification, self::BS, self::BR, self::HC, self::BC, self::H2V, self::H2, self::B2V, self::B2, self::H1V, self::H1, self::B1V, self::B1, self::H0V, self::H0 => 999, // 5 ans pour les autres
            // 5 ans standard
            self::SIR => 48, // 4 ans max avec examen intermédiaire
            // Contexte spécifique (généralement unique à 45 ans)
            default => 99999,
        };
    }
}
