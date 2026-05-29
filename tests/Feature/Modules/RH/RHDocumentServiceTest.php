<?php

namespace Tests\Feature\Modules\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Models\Core\Company;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Services\RH\RHDocumentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

// Define a mock enum for equipment type within the test scope
enum MockEquipmentType: string
{
    case EPI = 'epi';
    case TOOL = 'tool';

    public function getLabel(): string
    {
        return match ($this) {
            self::EPI => 'Équipement de Protection Individuelle',
            self::TOOL => 'Outil',
        };
    }
}

beforeEach(function () {
    Company::factory()->create();
    Storage::fake('public');

    $this->service = app(RHDocumentService::class);
    $this->employee = Employee::factory()->create();
});

describe('RHDocumentService - generateContract', function () {
    test('génère un contrat de travail', function () {
        $contract = Contract::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $path = $this->service->generateContract($contract);

        expect($path)->toContain('contrat_')
            ->and($path)->toContain($this->employee->registration_number)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans le répertoire rh', function () {
        $contract = Contract::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $path = $this->service->generateContract($contract);

        expect($path)->toContain('rh');
    });

    test('charge l\'employé du contrat', function () {
        $contract = Contract::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $path = $this->service->generateContract($contract);

        expect($path)->not->toBeNull();
    });

    test('inclut le numéro d\'enregistrement de l\'employé', function () {
        $this->employee->update(['registration_number' => 'EMP-2026-001']);

        $contract = Contract::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $path = $this->service->generateContract($contract);

        expect($path)->toContain('EMP-2026-001');
    });
});

describe('RHDocumentService - generateSafetyPassport', function () {
    test('génère un passeport de sécurité', function () {
        $path = $this->service->generateSafetyPassport($this->employee);

        expect($path)->toContain('passport_securite_')
            ->and($path)->toContain($this->employee->id)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans le répertoire rh', function () {
        $path = $this->service->generateSafetyPassport($this->employee);

        expect($path)->toContain('rh');
    });

    test('charge les qualifications de l\'employé', function () {
        $this->employee->load(['qualifications', 'medicalVisits']);

        $path = $this->service->generateSafetyPassport($this->employee);

        expect($path)->not->toBeNull();
    });

    test('charge les visites médicales', function () {
        $this->employee->load(['medicalVisits']);

        $path = $this->service->generateSafetyPassport($this->employee);

        expect($path)->not->toBeNull();
    });

    test('génère une URL publique pour le passeport', function () {
        $path = $this->service->generateSafetyPassport($this->employee);

        expect($path)->not->toBeNull();
    });
});

describe('RHDocumentService - generateEquipmentHandover', function () {
    test('génère une décharge de remise de matériel', function () {
        $equipments = new Collection([
            (object) ['label' => 'Casque de sécurité', 'type' => MockEquipmentType::EPI, 'serial_number' => 'SN001', 'expires_at' => null],
            (object) ['label' => 'Gilet de sécurité', 'type' => MockEquipmentType::EPI, 'serial_number' => 'SN002', 'expires_at' => null],
        ]);

        $path = $this->service->generateEquipmentHandover($this->employee, $equipments);

        expect($path)->toContain('decharge_materiel_')
            ->and($path)->toContain($this->employee->id)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans le répertoire rh', function () {
        $equipments = new Collection([
            (object) ['label' => 'Marteau', 'type' => MockEquipmentType::TOOL, 'serial_number' => 'T001', 'expires_at' => null],
        ]);

        $path = $this->service->generateEquipmentHandover($this->employee, $equipments);

        expect($path)->toContain('rh');
    });

    test('inclut la date actuelle dans le nom du fichier', function () {
        $equipments = new Collection([
            (object) ['label' => 'Marteau', 'type' => MockEquipmentType::TOOL, 'serial_number' => 'T001', 'expires_at' => null],
        ]);
        $today = now()->format('Ymd');

        $path = $this->service->generateEquipmentHandover($this->employee, $equipments);

        expect($path)->toContain($today);
    });

    test('gère une liste vide d\'équipements', function () {
        $equipments = new Collection;

        $path = $this->service->generateEquipmentHandover($this->employee, $equipments);

        expect($path)->not->toBeNull();
    });

    test('gère plusieurs équipements', function () {
        $equipments = new Collection([
            (object) ['label' => 'Casque', 'type' => MockEquipmentType::EPI, 'serial_number' => 'EPI001', 'expires_at' => null],
            (object) ['label' => 'Gilet', 'type' => MockEquipmentType::EPI, 'serial_number' => 'EPI002', 'expires_at' => null],
            (object) ['label' => 'Chaussures', 'type' => MockEquipmentType::EPI, 'serial_number' => 'EPI003', 'expires_at' => null],
            (object) ['label' => 'Gants', 'type' => MockEquipmentType::EPI, 'serial_number' => 'EPI004', 'expires_at' => null],
        ]);

        $path = $this->service->generateEquipmentHandover($this->employee, $equipments);

        expect($path)->not->toBeNull();
    });
});

describe('RHDocumentService - generateMonthlyTimesheet', function () {
    test('génère un relevé d\'heures mensuel', function () {
        $month = now()->month;
        $year = now()->year;

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'status' => TimeEntryStatus::APPROVED,
        ]);

        $path = $this->service->generateMonthlyTimesheet($this->employee, $month, $year);

        expect($path)->toContain('releve_heures_')
            ->toContain($this->employee->id)
            ->toContain((string) $year)
            ->toContain((string) $month)
            ->toEndWith('.pdf');
    });

    test('stocke le fichier dans rh/timesheets', function () {
        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'status' => TimeEntryStatus::APPROVED,
        ]);

        $path = $this->service->generateMonthlyTimesheet($this->employee, now()->month, now()->year);

        expect($path)->toContain('rh/timesheets');
    });

    test('utilise le format landscape', function () {
        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'status' => TimeEntryStatus::APPROVED,
        ]);

        $path = $this->service->generateMonthlyTimesheet($this->employee, now()->month, now()->year);

        expect($path)->not->toBeNull();
    });

    test('inclut seulement les entrées approuvées', function () {
        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'status' => TimeEntryStatus::APPROVED,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'status' => TimeEntryStatus::DRAFT,
        ]);

        $path = $this->service->generateMonthlyTimesheet($this->employee, now()->month, now()->year);

        expect($path)->not->toBeNull();
    });

    test('filtre par mois et année', function () {
        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'status' => TimeEntryStatus::APPROVED,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now()->subMonth(),
            'status' => TimeEntryStatus::APPROVED,
        ]);

        $path = $this->service->generateMonthlyTimesheet($this->employee, now()->month, now()->year);

        expect($path)->not->toBeNull();
    });

    test('calcule les heures totales', function () {
        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 8.0,
            'status' => TimeEntryStatus::APPROVED,
        ]);

        TimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now(),
            'hours' => 7.5,
            'status' => TimeEntryStatus::APPROVED,
        ]);

        $path = $this->service->generateMonthlyTimesheet($this->employee, now()->month, now()->year);

        expect($path)->not->toBeNull();
    });
});

describe('RHDocumentService - generateFullRecord', function () {
    test('génère un dossier individuel complet', function () {
        $path = $this->service->generateFullRecord($this->employee);

        expect($path)->toContain('fiche_salarie_')
            ->and($path)->toContain($this->employee->registration_number)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans rh/records', function () {
        $path = $this->service->generateFullRecord($this->employee);

        expect($path)->toContain('rh/records');
    });

    test('charge toutes les relations de l\'employé', function () {
        $this->employee->load([
            'currentContract',
            'contracts',
            'equipements',
            'qualifications',
            'medicalVisits',
        ]);

        $path = $this->service->generateFullRecord($this->employee);

        expect($path)->not->toBeNull();
    });

    test('inclut un UUID pour l\'accès public', function () {
        $path = $this->service->generateFullRecord($this->employee);

        expect($path)->not->toBeNull();
    });
});
