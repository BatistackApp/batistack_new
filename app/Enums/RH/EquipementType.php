<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum EquipementType: string implements HasIcon, HasLabel
{
    case PPE = 'ppe';           // EPI (Casque, Harnais, Gants)
    case TOOL = 'tool';         // Outillage (Perceuse, Laser)
    case VEHICLE = 'vehicle';   // Véhicule (Fonction/Service)
    case IT = 'it';             // Informatique (PC, Mobile)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PPE => 'Équipement de Protection (EPI)',
            self::TOOL => 'Outillage Électro-portatif',
            self::VEHICLE => 'Véhicule de fonction/service',
            self::IT => 'Matériel Informatique / Téléphonie',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::PPE => Phosphor::ShieldCheck,
            self::TOOL => Phosphor::Hammer,
            self::VEHICLE => Phosphor::Car,
            self::IT => Phosphor::DeviceMobile,
        };
    }
}
