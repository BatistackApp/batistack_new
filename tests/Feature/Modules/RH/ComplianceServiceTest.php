<?php

use App\Models\RH\Employee;
use App\Models\RH\Qualification;
use App\Models\User;
use App\Services\RH\ComplianceService;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

describe('Sécurité et Habilitations', function () {

    test('il détecte les habilitations arrivant à expiration', function () {
        $employee = Employee::factory()->create();

        // Qualification expirant dans 15 jours
        Qualification::factory()->create([
            'employee_id' => $employee->id,
            'expires_at' => now()->addDays(15),
        ]);

        $service = new ComplianceService;
        $expiring = $service->getExpiringQualifications(30);

        expect($expiring)->toHaveCount(1)
            ->and($expiring->first()->employee_id)->toBe($employee->id);
    });
});
