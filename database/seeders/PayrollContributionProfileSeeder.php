<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Paie\PayrollContributionProfile;
use App\Enums\Paie\ContributionBaseFormula;

class PayrollContributionProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profile = PayrollContributionProfile::updateOrCreate(
            ['code' => 'BTP_ETAM'],
            [
                'name' => 'Bâtiment (ETAM)',
                'description' => 'Profil de cotisations pour les employés ETAM du Bâtiment',
                'meal_allowance_amount' => 11.20,
            ]
        );

        $rates = [
            // --- Santé ---
            [
                'category' => 'Santé',
                'label' => 'Sécurité Sociale - Mal. Mat. Inval. Décès',
                'employee_rate' => 0.0000,
                'employer_rate' => 13.0000,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
            ],
            [
                'category' => 'Santé',
                'label' => 'Complémentaire - Incap. Inval. Décès',
                'employee_rate' => 0.6000,
                'employer_rate' => 1.2500,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
                'is_fiscally_reintegrated' => true,
            ],
            [
                'category' => 'Santé',
                'label' => 'Complémentaire - Santé',
                // Le montant est fixe (17.50€) sur le bulletin, on simule un taux à 0.5354% sur la base de 3268.49€ pour le moment
                'employee_rate' => 0.5354,
                'employer_rate' => 0.5354,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
                'is_fiscally_reintegrated' => true,
            ],

            // --- Accidents du travail ---
            [
                'category' => 'Accidents du travail & mal. professionnelles',
                'label' => 'Accidents du travail & mal. professionnelles',
                'employee_rate' => 0.0000,
                'employer_rate' => 7.3900,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
            ],

            // --- Retraite ---
            [
                'category' => 'Retraite',
                'label' => 'Sécurité Sociale plafonnée',
                'employee_rate' => 6.9000,
                'employer_rate' => 8.5500,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
            ],
            [
                'category' => 'Retraite',
                'label' => 'Sécurité Sociale déplafonnée',
                'employee_rate' => 0.4000,
                'employer_rate' => 2.1100,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
            ],
            [
                'category' => 'Retraite',
                'label' => 'Complémentaire Tranche 1',
                'employee_rate' => 4.2600,
                'employer_rate' => 5.7600,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
            ],

            // --- Famille ---
            [
                'category' => 'Famille',
                'label' => 'Famille',
                'employee_rate' => 0.0000,
                'employer_rate' => 5.2500,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
            ],

            // --- Assurance chômage ---
            [
                'category' => 'Assurance chômage',
                'label' => 'Assurance chômage',
                'employee_rate' => 0.0000,
                'employer_rate' => 4.2500,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
            ],

            // --- Cot. statutaires ou prévues par la conv. coll. ---
            [
                'category' => 'Cot. statutaires ou prévues par la conv. coll.',
                'label' => 'Congés payés',
                'employee_rate' => 0.0000,
                'employer_rate' => 20.7000,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
            ],
            [
                'category' => 'Cot. statutaires ou prévues par la conv. coll.',
                'label' => 'OPP BTP',
                'employee_rate' => 0.0000,
                'employer_rate' => 0.1100,
                'base_formula' => ContributionBaseFormula::OPPBTP_BASE,
                'is_deductible' => true,
            ],

            // --- Autres contributions dues par l'employeur ---
            [
                'category' => 'Autres contributions dues par l\'employeur',
                'label' => 'Autres contributions dues par l\'employeur (Brut)',
                'employee_rate' => 0.0000,
                'employer_rate' => 1.1860,
                'base_formula' => ContributionBaseFormula::GROSS_SALARY,
                'is_deductible' => true,
            ],
            [
                'category' => 'Autres contributions dues par l\'employeur',
                'label' => 'Autres contributions dues par l\'employeur (Base Congés)',
                'employee_rate' => 0.0000,
                'employer_rate' => 1.3300,
                'base_formula' => ContributionBaseFormula::OPPBTP_BASE,
                'is_deductible' => true,
            ],

            // --- CSG / CRDS ---
            [
                'category' => 'CSG / CRDS',
                'label' => 'CSG déduct. de l\'impôt sur le revenu',
                'employee_rate' => 6.8000,
                'employer_rate' => 0.0000,
                'base_formula' => ContributionBaseFormula::CSG_BASE,
                'is_deductible' => true,
            ],
            [
                'category' => 'CSG / CRDS',
                'label' => 'CSG/CRDS non déduct. de l\'impôt sur le revenu',
                'employee_rate' => 2.9000,
                'employer_rate' => 0.0000,
                'base_formula' => ContributionBaseFormula::CSG_BASE,
                'is_deductible' => false,
            ],
        ];

        // On supprime les anciens taux pour éviter les doublons au relancement
        $profile->rates()->delete();

        foreach ($rates as $rateData) {
            $profile->rates()->create($rateData);
        }
    }
}
