<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionReportTemplate;
use App\Models\Tiers\ThirdParty;
use App\Services\Interventions\InterventionManagementService;
use App\Services\Interventions\InterventionStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::statement('PRAGMA foreign_keys=OFF;');
    $this->service = new InterventionManagementService;
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
});

function makeReportTemplate(array $schema, array $overrides = []): InterventionReportTemplate
{
    return InterventionReportTemplate::create(array_merge([
        'name' => 'Rapport SAV',
        'intervention_type' => InterventionType::REGIE,
        'is_active' => true,
        'schema' => $schema,
    ], $overrides));
}

function makeIntervention(array $overrides = []): Intervention
{
    return Intervention::factory()->create(array_merge([
        'company_id' => test()->company->id,
        'third_party_id' => test()->client->id,
        'type' => InterventionType::REGIE,
        'status' => InterventionStatus::EN_COURS,
    ], $overrides));
}

function requiredSchema(): array
{
    return [
        ['type' => 'text_input', 'data' => ['name' => 'constat', 'label' => 'Constat', 'required' => true]],
        ['type' => 'checkbox', 'data' => ['name' => 'conforme', 'label' => 'Conforme', 'required' => false]],
    ];
}

describe('InterventionReportTemplate', function () {
    test('persists the schema as an array and the type enum', function () {
        $template = makeReportTemplate(requiredSchema());

        expect($template->schema)->toBe(requiredSchema())
            ->and($template->intervention_type)->toBe(InterventionType::REGIE)
            ->and($template->is_active)->toBeTrue();
    });

    test('applicableReportTemplate resolves the active template for the intervention type', function () {
        $template = makeReportTemplate(requiredSchema());
        $intervention = makeIntervention();

        expect($intervention->applicableReportTemplate()?->is($template))->toBeTrue();
    });

    test('applicableReportTemplate prefers the linked active template', function () {
        $other = makeReportTemplate(requiredSchema());
        $linked = makeReportTemplate(requiredSchema(), ['name' => 'Spécial']);

        $intervention = makeIntervention(['report_template_id' => $linked->id]);

        expect($intervention->applicableReportTemplate()?->is($linked))->toBeTrue();
    });

    test('applicableReportTemplate ignores inactive linked templates and falls back by type', function () {
        $inactive = makeReportTemplate(requiredSchema(), ['is_active' => false, 'name' => 'Ancien']);
        $active = makeReportTemplate(requiredSchema());

        $intervention = makeIntervention(['report_template_id' => $inactive->id]);

        expect($intervention->applicableReportTemplate()?->is($active))->toBeTrue();
    });

    test('applicableReportTemplate is null when no active template matches', function () {
        $intervention = makeIntervention();

        expect($intervention->applicableReportTemplate())->toBeNull();
    });
});

describe('Intervention closure validation', function () {
    test('completeIntervention is blocked when a required field is missing', function () {
        $template = makeReportTemplate(requiredSchema());
        $intervention = makeIntervention();

        expect(fn () => $this->service->completeIntervention($intervention))
            ->toThrow(DomainException::class, 'Constat');
    });

    test('completeIntervention succeeds when all required fields are filled', function () {
        $template = makeReportTemplate(requiredSchema());
        $intervention = makeIntervention([
            'report_template_id' => $template->id,
            'report_data' => ['constat' => 'Moteur remplacé', 'conforme' => true],
        ]);

        $stockMock = Mockery::mock(InterventionStockService::class);
        $stockMock->shouldReceive('processMaterials')->once();
        app()->instance(InterventionStockService::class, $stockMock);

        expect($this->service->completeIntervention($intervention))->toBeTrue()
            ->and($intervention->fresh()->status)->toBe(InterventionStatus::TERMINEE);
    });

    test('validation does not block when the template is inactive', function () {
        makeReportTemplate(requiredSchema(), ['is_active' => false]);
        $intervention = makeIntervention();

        $stockMock = Mockery::mock(InterventionStockService::class);
        $stockMock->shouldReceive('processMaterials')->once();
        app()->instance(InterventionStockService::class, $stockMock);

        expect($this->service->completeIntervention($intervention))->toBeTrue();
    });

    test('validation does not block when there is no template for the type', function () {
        $intervention = makeIntervention();

        $stockMock = Mockery::mock(InterventionStockService::class);
        $stockMock->shouldReceive('processMaterials')->once();
        app()->instance(InterventionStockService::class, $stockMock);

        expect($this->service->completeIntervention($intervention))->toBeTrue();
    });

    test('an unchecked required checkbox counts as missing', function () {
        $schema = [
            ['type' => 'checkbox', 'data' => ['name' => 'conforme', 'label' => 'Conforme', 'required' => true]],
        ];
        makeReportTemplate($schema);
        $intervention = makeIntervention(['report_data' => ['conforme' => false]]);

        expect(fn () => $this->service->completeIntervention($intervention))
            ->toThrow(DomainException::class);
    });
});
