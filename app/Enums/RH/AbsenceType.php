<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use ToneGabes\Filament\Icons\Enums\Phosphor;

enum AbsenceType: string implements HasColor, HasIcon, HasLabel
{
    case PAID_LEAVE = 'paid_leave';     // Congés Payés
    case RTT = 'rtt';                   // RTT
    case SICK_LEAVE = 'sick_leave';     // Maladie
    case UNPAID_LEAVE = 'unpaid_leave'; // Congé sans solde
    case WORK_ACCIDENT = 'work_accident'; // Accident du travail (Critique BTP)
    case UNJUSTIFIED = 'unjustified'; // Absence non justifiée'
    case WEATHER_DAY = 'weather_day'; // Chomage Intempérie

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PAID_LEAVE => 'Congés Payés',
            self::RTT => 'RTT',
            self::SICK_LEAVE => 'Arrêt Maladie',
            self::UNPAID_LEAVE => 'Sans Solde',
            self::WORK_ACCIDENT => 'Accident du Travail',
            self::UNJUSTIFIED => 'Non justifié',
            self::WEATHER_DAY => 'Chômage Intempérie',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PAID_LEAVE => 'success',
            self::RTT => 'info',
            self::SICK_LEAVE => 'warning',
            self::UNPAID_LEAVE, self::WEATHER_DAY => 'gray',
            self::WORK_ACCIDENT, self::UNJUSTIFIED => 'danger',
        };
    }

    public function getIcon(): Phosphor
    {
        return match ($this) {
            self::PAID_LEAVE => Phosphor::CalendarCheck,
            self::RTT => Phosphor::Hourglass,
            self::SICK_LEAVE => Phosphor::FirstAidKit,
            self::UNPAID_LEAVE => Phosphor::Coins,
            self::WORK_ACCIDENT => Phosphor::WarningOctagon,
            self::UNJUSTIFIED => Phosphor::XCircle,
            self::WEATHER_DAY => Phosphor::CloudRain,
        };
    }
}
