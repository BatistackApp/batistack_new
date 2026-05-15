<?php

namespace App\Services\RH;

use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Services\Core\CompanyService;
use App\Services\Core\GoogleMapsService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;

class PayrollVariableService
{
    public function __construct(
        protected CompanyService $companyService,
        protected GoogleMapsService $googleMapsService
    ) {}

    /**
     * Calcule la zone ou le Grand Déplacement pour un trajet.
     *
     * @return array ['zone' => int|null, 'is_gd' => bool, 'allowance' => float]
     *
     * @throws ConnectionException
     */
    public function calculateTravelAllowance(string $siteAddress): array
    {
        $company = $this->companyService->getCompany();
        $hqAddress = "{$company->address}, {$company->zip_code} {$company->city}";

        $matrix = $this->googleMapsService->getDistanceMatrix($hqAddress, $siteAddress);

        if (! $matrix) {
            return ['zone' => null, 'is_gd' => false, 'allowance' => 0];
        }

        $distanceKm = $matrix['distance_value'] / 1000;

        // Règle Grand Déplacement (> 50km)
        if ($distanceKm > 50) {
            return [
                'zone' => null,
                'is_gd' => true,
                'allowance' => 96.00, // Forfait fixe Batistack
            ];
        }

        // Mapping des Zones 1 à 5
        $zone = (int) ceil($distanceKm / 10);
        $zone = min($zone, 5);

        return [
            'zone' => $zone,
            'is_gd' => false,
            'allowance' => 0, // L'indemnité de zone dépend de la grille Core/RH
        ];
    }

    /**
     * Calcule les heures supplémentaires hebdomadaires d'un employé.
     *
     * @param  Carbon  $startDate  Lundi de la semaine
     */
    public function calculateWeeklyOvertime(Employee $employee, Carbon $startDate): array
    {
        $endDate = (clone $startDate)->endOfWeek();

        $totalWorkHours = TimeEntry::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->sum('hours');

        $totalTravelHours = TimeEntry::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->sum('travel_hours');

        $contractHours = $employee->currentContract?->weekly_hours ?? 35;

        $overtime25 = 0;
        $overtime50 = 0;

        if ($totalWorkHours > $contractHours) {
            $diff = $totalWorkHours - $contractHours;
            $overtime25 = min($diff, 8);
            if ($diff > 8) {
                $overtime50 = $diff - 8;
            }
        }

        return [
            'normal_hours' => min($totalWorkHours, $contractHours),
            'overtime_25' => $overtime25,
            'overtime_50' => $overtime50,
            'total_work' => $totalWorkHours,
            'total_travel' => $totalTravelHours, // AJOUT : Distinction trajet
        ];
    }
}
