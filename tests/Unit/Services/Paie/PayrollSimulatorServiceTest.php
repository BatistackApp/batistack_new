<?php

use App\Models\Paie\PayrollContributionProfile;
use App\Services\Paie\PayrollSimulatorService;
use App\Enums\Paie\ContributionBaseFormula;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can simulate from gross', function () {
    $profile = PayrollContributionProfile::create([
        'name' => 'Profil Test',
        'code' => 'TEST',
        'is_active' => true,
    ]);

    $profile->rates()->create([
        'category' => 'Santé',
        'label' => 'Sécurité Sociale - Maladie',
        'base_formula' => ContributionBaseFormula::GROSS_SALARY,
        'employee_rate' => 0.0,
        'employer_rate' => 7.0,
        'is_deductible' => true,
        'is_fiscally_reintegrated' => false,
        'valid_from' => now()->subYear(),
    ]);

    $profile->rates()->create([
        'category' => 'Retraite',
        'label' => 'Retraite complémentaire',
        'base_formula' => ContributionBaseFormula::GROSS_SALARY,
        'employee_rate' => 3.15,
        'employer_rate' => 4.72,
        'is_deductible' => true,
        'is_fiscally_reintegrated' => false,
        'valid_from' => now()->subYear(),
    ]);

    $profile->rates()->create([
        'category' => 'CSG',
        'label' => 'CSG déductible',
        'base_formula' => ContributionBaseFormula::CSG_BASE,
        'employee_rate' => 6.8,
        'employer_rate' => 0.0,
        'is_deductible' => true,
        'is_fiscally_reintegrated' => false,
        'valid_from' => now()->subYear(),
    ]);

    $profile->rates()->create([
        'category' => 'CSG',
        'label' => 'CSG non déductible',
        'base_formula' => ContributionBaseFormula::CSG_BASE,
        'employee_rate' => 2.4,
        'employer_rate' => 0.0,
        'is_deductible' => false,
        'is_fiscally_reintegrated' => false,
        'valid_from' => now()->subYear(),
    ]);

    $simulator = new PayrollSimulatorService();
    $result = $simulator->simulateFromGross(2000, $profile);

    // Assertions
    expect($result['gross_salary'])->toBe((float)2000);
    
    $csgBase = round(2000 * 0.9825, 2);
    
    $expectedEmployee = round(2000 * (3.15/100), 2) + round($csgBase * (6.8/100), 2) + round($csgBase * (2.4/100), 2);
    $expectedEmployer = round(2000 * (7.0/100), 2) + round(2000 * (4.72/100), 2);
    
    expect($result['total_employee_contributions'])->toBe((float)$expectedEmployee);
    expect($result['total_employer_contributions'])->toBe((float)$expectedEmployer);
    
    $expectedNetSocial = round(2000 - $expectedEmployee, 2);
    expect($result['net_social'])->toBe((float)$expectedNetSocial);
    
    $csgNonDeductible = round($csgBase * (2.4/100), 2);
    $expectedTaxable = round($expectedNetSocial + $csgNonDeductible, 2);
    expect($result['taxable_net'])->toBe((float)$expectedTaxable);
});

it('can simulate from net using approximation', function () {
    $profile = PayrollContributionProfile::create([
        'name' => 'Profil Test',
        'code' => 'TEST',
        'is_active' => true,
    ]);

    $profile->rates()->create([
        'category' => 'Retraite',
        'label' => 'Retraite',
        'base_formula' => ContributionBaseFormula::GROSS_SALARY,
        'employee_rate' => 20.0, // 20% de charges pour simplifier
        'employer_rate' => 40.0,
        'is_deductible' => true,
        'is_fiscally_reintegrated' => false,
        'valid_from' => now()->subYear(),
    ]);

    $simulator = new PayrollSimulatorService();
    
    // Si brut = 2500, cotis_sal = 2500 * 20% = 500. Net = 2000.
    // On veut tester l'inverse : cible Net = 2000. On s'attend à trouver Brut = 2500.
    $result = $simulator->simulateFromNet(2000, $profile);
    
    // Tolérance d'1 centime
    $this->assertEqualsWithDelta(2500, $result['gross_salary'], 0.01);
    $this->assertEqualsWithDelta(2000, $result['net_social'], 0.01);
});
