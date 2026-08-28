<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Articles\Item;
use App\Models\Core\Company;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionMaterial;
use App\Models\Interventions\InterventionWorker;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Notifications\Interventions\InterventionScheduledNotification;
use App\Services\Core\DocumentService;
use App\Services\Core\PdfStamperService;
use App\Services\Interventions\InterventionPdfService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::disableForeignKeyConstraints();
    $this->company = Company::factory()->create();
});

describe('Interventions Minor Components', function () {
    test('InterventionStatus enum has correct values and labels', function () {
        expect(InterventionStatus::BROUILLON->value)->toBe('brouillon')
            ->and(InterventionStatus::BROUILLON->getLabel())->toBe('Brouillon')
            ->and(InterventionStatus::BROUILLON->getColor())->toBe('gray')
            ->and(InterventionStatus::BROUILLON->getIcon())->toBe('heroicon-m-pencil');
    });

    test('InterventionType enum has correct values and labels', function () {
        expect(InterventionType::REGIE->value)->toBe('regie')
            ->and(InterventionType::REGIE->getLabel())->toBe('Régie')
            ->and(InterventionType::REGIE->getColor())->toBe('info');
    });

    test('ClientEquipment model relations', function () {
        $client = ThirdParty::factory()->create();
        $equipment = ClientEquipment::create(['third_party_id' => $client->id, 'company_id' => $this->company->id, 'name' => 'Eq', 'serial_number' => '1']);
        expect($equipment->thirdParty)->not->toBeNull()
            ->and($equipment->interventions())->toBeInstanceOf(HasMany::class);
    });

    test('InterventionMaterial model relations', function () {
        $client = ThirdParty::factory()->create();
        $item = Item::factory()->create();
        $intervention = Intervention::factory()->create(['third_party_id' => $client->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::BROUILLON]);
        $material = InterventionMaterial::create([
            'intervention_id' => $intervention->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'selling_price' => 10,
        ]);
        expect($material->intervention)->not->toBeNull()
            ->and($material->item)->not->toBeNull();
    });

    test('InterventionWorker model relations', function () {
        $client = ThirdParty::factory()->create();
        $employee = Employee::factory()->create();
        $intervention = Intervention::factory()->create(['third_party_id' => $client->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::BROUILLON]);
        $worker = InterventionWorker::create([
            'intervention_id' => $intervention->id,
            'employee_id' => $employee->id,
        ]);
        expect($worker->intervention)->not->toBeNull()
            ->and($worker->employee)->not->toBeNull();
    });

    test('Intervention model relations and helpers', function () {
        $client = ThirdParty::factory()->create();
        $intervention = Intervention::factory()->create(['third_party_id' => $client->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::BROUILLON]);
        expect($intervention->company)->not->toBeNull()
            ->and($intervention->thirdParty)->not->toBeNull()
            ->and($intervention->chantier)->toBeNull()
            ->and($intervention->materials())->toBeInstanceOf(HasMany::class)
            ->and($intervention->workers())->toBeInstanceOf(HasMany::class);
    });

    test('InterventionScheduledNotification can be sent', function () {
        Notification::fake();
        $client = ThirdParty::factory()->create();
        $employee = Employee::factory()->create();
        $intervention = Intervention::factory()->create(['third_party_id' => $client->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::BROUILLON]);

        $employee->notify(new InterventionScheduledNotification($intervention));

        Notification::assertSentTo($employee, InterventionScheduledNotification::class, function ($notification) use ($intervention, $employee) {
            $array = $notification->toArray($employee);

            return $array['intervention_id'] === $intervention->id;
        });
    });

    test('InterventionPdfService generates and stamps pdf', function () {
        $client = ThirdParty::factory()->create();
        $intervention = Intervention::factory()->create(['third_party_id' => $client->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::BROUILLON]);

        // Mock DocumentService
        $documentService = Mockery::mock(DocumentService::class);
        $documentService->shouldReceive('generate')->once()->andReturn('/tmp/fake.pdf');

        // Mock PdfStamperService
        $pdfStamperService = Mockery::mock(PdfStamperService::class);

        $service = new InterventionPdfService($documentService, $pdfStamperService);

        $pdfPath = $service->generatePdf($intervention);

        expect($pdfPath)->toBe('/tmp/fake.pdf');
    });
});
