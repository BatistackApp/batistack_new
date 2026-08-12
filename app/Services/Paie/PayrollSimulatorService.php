<?php

namespace App\Services\Paie;

use App\Enums\Paie\ContributionBaseFormula;
use App\Models\Paie\PayrollContributionProfile;
use Carbon\Carbon;

class PayrollSimulatorService
{
    /**
     * Calcule les nets et le coût employeur à partir d'un salaire brut.
     * 
     * @param float $grossSalary Le salaire brut de base
     * @param PayrollContributionProfile $profile Le profil de cotisations à utiliser
     * @param Carbon|null $date La date pour laquelle on veut les taux (par défaut: maintenant)
     * @return array Les résultats de la simulation
     */
    public function simulateFromGross(float $grossSalary, PayrollContributionProfile $profile, ?\Carbon\CarbonInterface $date = null, float $ijssBrutes = 0.0): array
    {
        $date = $date ?? now();
        $validRates = $profile->rates()->validAt($date)->get();

        $totalEmployeeContributions = 0;
        $totalEmployerContributions = 0;
        $fiscalReintegration = 0;
        $csgNonDeductibleAmount = 0;

        // Étape 1 : Pré-calcul de la réintégration fiscale
        foreach ($validRates as $rate) {
            if ($rate->is_fiscally_reintegrated && $rate->base_formula === ContributionBaseFormula::GROSS_SALARY) {
                $fiscalReintegration += round($grossSalary * ($rate->employer_rate / 100), 2);
            }
        }

        // Étape 2 : Calcul des bases spécifiques (notamment CSG)
        $csgBase = round($grossSalary * 0.9825, 2); // Base CSG standard (98.25% du brut)
        $csgBase += $fiscalReintegration;

        $lines = [];

        // Étape 3 : Calcul détaillé des lignes de cotisations
        foreach ($validRates as $rate) {
            $base = $grossSalary;
            
            if ($rate->base_formula === ContributionBaseFormula::CSG_BASE) {
                $base = $csgBase;
            } elseif ($rate->base_formula === ContributionBaseFormula::OPPBTP_BASE) {
                $base = round($grossSalary * 1.1394, 2);
            }

            $employeeAmount = round($base * ($rate->employee_rate / 100), 2);
            $employerAmount = round($base * ($rate->employer_rate / 100), 2);

            $lines[] = [
                'category' => $rate->category,
                'label' => $rate->label,
                'base' => $base,
                'employee_rate' => $rate->employee_rate,
                'employer_rate' => $rate->employer_rate,
                'employee_amount' => $employeeAmount,
                'employer_amount' => $employerAmount,
            ];

            $totalEmployeeContributions += $employeeAmount;
            $totalEmployerContributions += $employerAmount;

            if (!$rate->is_deductible) {
                $csgNonDeductibleAmount += $employeeAmount;
            }
        }

        // CSG sur les IJSS Brutes (6.2% deductible + 0.5% non deduc = 6.7%)
        if ($ijssBrutes > 0) {
            $csgDeductibleIjss = round($ijssBrutes * 0.062, 2);
            $csgNonDeductibleIjss = round($ijssBrutes * 0.005, 2);
            
            $lines[] = [
                'category' => 'csg_crds_ijss',
                'label' => 'CSG/CRDS sur IJSS',
                'base' => $ijssBrutes,
                'employee_rate' => 6.7,
                'employer_rate' => 0,
                'employee_amount' => $csgDeductibleIjss + $csgNonDeductibleIjss,
                'employer_amount' => 0,
            ];

            $totalEmployeeContributions += ($csgDeductibleIjss + $csgNonDeductibleIjss);
            $csgNonDeductibleAmount += $csgNonDeductibleIjss;
        }

        // Calculs finaux
        // Le net social inclut les IJSS Brutes car elles sont versées à l'employé
        $netSocial = round(($grossSalary + $ijssBrutes) - $totalEmployeeContributions, 2);
        
        // Net imposable = Net social + CSG non déductible + réintégration fiscale
        $taxableNet = round($netSocial + $csgNonDeductibleAmount + $fiscalReintegration, 2);
        
        $employerCost = round($grossSalary + $totalEmployerContributions, 2);

        return [
            'gross_salary' => $grossSalary,
            'ijss_brutes' => $ijssBrutes,
            'net_social' => $netSocial,
            'taxable_net' => $taxableNet,
            'total_employee_contributions' => $totalEmployeeContributions,
            'total_employer_contributions' => $totalEmployerContributions,
            'employer_cost' => $employerCost,
            'lines' => $lines,
        ];
    }

    /**
     * Retrouve le salaire brut nécessaire pour atteindre un Net Social ciblé
     * via un algorithme d'approximation par dichotomie.
     * 
     * @param float $targetNet Le net social visé
     * @param PayrollContributionProfile $profile Le profil de cotisations
     * @param \Carbon\CarbonInterface|null $date
     * @return array Les résultats de la simulation correspondant au brut trouvé
     */
    public function simulateFromNet(float $targetNet, PayrollContributionProfile $profile, ?\Carbon\CarbonInterface $date = null): array
    {
        // Limites de la recherche binaire (on suppose qu'un brut est forcément entre le net et le net * 3)
        $lowGross = $targetNet; 
        $highGross = $targetNet * 3;
        
        $bestSimulation = null;
        $tolerance = 0.01;
        
        // Sécurité pour éviter une boucle infinie (max 100 itérations)
        for ($i = 0; $i < 100; $i++) {
            $midGross = round(($lowGross + $highGross) / 2, 4);
            $simulation = $this->simulateFromGross($midGross, $profile, $date);
            
            $currentNet = $simulation['net_social'];
            
            if (abs($currentNet - $targetNet) <= $tolerance) {
                $bestSimulation = $simulation;
                break;
            }
            
            if ($currentNet < $targetNet) {
                $lowGross = $midGross;
            } else {
                $highGross = $midGross;
            }
            
            $bestSimulation = $simulation;
        }
        
        return $bestSimulation;
    }
}
