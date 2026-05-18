<?php

use App\Enums\RH\CertificationSymbol;
use App\Enums\RH\MedicalAptitude;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use App\Models\RH\Qualification;
use App\Services\Chantiers\ChantierComplianceService;

test('il détecte les non-conformités de l’équipe assignée', function () {
    $service = new ChantierComplianceService;
    $chantier = Chantier::factory()->create();

    $employee = Employee::factory()->create(['first_name' => 'Jean', 'last_name' => 'Test']);
    $chantier->members()->attach($employee);

    // Cas : Visite médicale manquante
    $res = $service->checkTeamCompliance($chantier);
    expect($res['is_compliant'])->toBeFalse()
        ->and($res['messages'][0])->toContain('Visite médicale manquante');

    // On régularise le médical mais on ajoute un CACES expiré
    MedicalVisit::factory()->create([
        'employee_id' => $employee->id,
        'next_due_date' => now()->addYear(),
        'aptitude' => MedicalAptitude::FIT,
    ]);

    Qualification::factory()->create([
        'employee_id' => $employee->id,
        'expires_at' => now()->subDay(),
        'label' => CertificationSymbol::R482,
        'type' => \App\Enums\RH\QualificationType::CACES,
    ]);

    $res = $service->checkTeamCompliance($chantier->refresh());
    expect($res['is_compliant'])->toBeFalse()
        ->and($res['messages'][0])->toContain('1 habilitation(s) expirée(s) pour Jean Test');
});
