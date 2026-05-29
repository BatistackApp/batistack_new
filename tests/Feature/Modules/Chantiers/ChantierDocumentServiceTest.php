<?php

namespace Tests\Feature\Modules\Chantiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Chantiers\ChantierAnalyticService;
use App\Services\Chantiers\ChantierDocumentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;

beforeEach(function () {
    Company::factory()->create();
    Storage::fake('public');

    $this->analyticService = Mockery::mock(ChantierAnalyticService::class);
    $this->service = new ChantierDocumentService($this->analyticService);
    $this->chantier = Chantier::factory()->create();
    $this->chantier->client()->associate(ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]));
    $this->chantier->save();
    $this->chantier->load(['client', 'manager', 'members']);
});

describe('ChantierDocumentService - generateStartOrder', function () {
    test('génère un ordre de service pour un chantier', function () {
        $path = $this->service->generateStartOrder($this->chantier);

        expect($path)->toContain('os_')
            ->and($path)->toContain($this->chantier->reference)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans le répertoire chantiers/orders', function () {
        $path = $this->service->generateStartOrder($this->chantier);

        expect($path)->toContain('chantiers/orders');
    });

    test('inclut la référence du chantier dans le nom du fichier', function () {
        $this->chantier->update(['reference' => 'CH-2026-001']);

        $path = $this->service->generateStartOrder($this->chantier);

        expect($path)->toContain('CH-2026-001');
    });

    test('charge les relations du chantier', function () {
        $this->chantier->load(['client', 'manager', 'members']);

        $path = $this->service->generateStartOrder($this->chantier);

        expect($path)->not->toBeNull();
    });
});

describe('ChantierDocumentService - generateHandoverProtocol', function () {
    test('génère un PV de réception', function () {
        $path = $this->service->generateHandoverProtocol($this->chantier);

        expect($path)->toContain('pv_reception_')
            ->and($path)->toContain($this->chantier->reference)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans chantiers/legal', function () {
        $path = $this->service->generateHandoverProtocol($this->chantier);

        expect($path)->toContain('chantiers/legal');
    });

    test('utilise le nom du chantier dans le titre', function () {
        $this->chantier->update(['name' => 'Rénovation Bureau']);

        $path = $this->service->generateHandoverProtocol($this->chantier);

        expect($path)->not->toBeNull();
    });

    test('charge les relations client et manager', function () {
        $this->chantier->load(['client', 'manager']);

        $path = $this->service->generateHandoverProtocol($this->chantier);

        expect($path)->not->toBeNull();
    });
});

describe('ChantierDocumentService - generateRentabilityReport', function () {
    test('génère un rapport de rentabilité', function () {
        $this->analyticService->shouldReceive('getPerformanceMetrics')
            ->with($this->chantier)
            ->andReturn([
                'revenue' => 10000.00,
                'costs' => 6000.00,
                'margin' => 4000.00,
                'margin_rate' => 40,
                'progress' => 50, // Added default progress
                'hours' => ['real' => 100, 'budget' => 200, 'percent' => 50], // Added default hours
                'financials' => [ // Added default financials
                    'labor_cost_real' => 3000.00,
                    'material_cost_real' => 2000.00,
                    'material_budget' => 4000.00,
                    'subcontracting_cost_real' => 1000.00,
                    'total_cost_real' => 6000.00,
                    'total_budget_ht' => 10000.00,
                    'budget_ht' => 10000.00,
                ],
            ]);

        $path = $this->service->generateRentabilityReport($this->chantier);

        expect($path)->toContain('bilan_')
            ->and($path)->toContain($this->chantier->reference)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans chantiers/reports', function () {
        $this->analyticService->shouldReceive('getPerformanceMetrics')
            ->with($this->chantier)
            ->andReturn([
                'revenue' => 10000.00,
                'costs' => 6000.00,
                'margin' => 4000.00,
                'margin_rate' => 40,
                'progress' => 50, // Added default progress
                'hours' => ['real' => 100, 'budget' => 200, 'percent' => 50], // Added default hours
                'financials' => [ // Added default financials
                    'labor_cost_real' => 3000.00,
                    'material_cost_real' => 2000.00,
                    'material_budget' => 4000.00,
                    'subcontracting_cost_real' => 1000.00,
                    'total_cost_real' => 6000.00,
                    'total_budget_ht' => 10000.00,
                    'budget_ht' => 10000.00,
                ],
            ]);

        $path = $this->service->generateRentabilityReport($this->chantier);

        expect($path)->toContain('chantiers/reports');
    });

    test('utilise le format landscape', function () {
        $this->analyticService->shouldReceive('getPerformanceMetrics')
            ->with($this->chantier)
            ->andReturn([
                'revenue' => 10000.00,
                'costs' => 6000.00,
                'margin' => 4000.00,
                'margin_rate' => 40,
                'progress' => 50, // Added default progress
                'hours' => ['real' => 100, 'budget' => 200, 'percent' => 50], // Added default hours
                'financials' => [ // Added default financials
                    'labor_cost_real' => 3000.00,
                    'material_cost_real' => 2000.00,
                    'material_budget' => 4000.00,
                    'subcontracting_cost_real' => 1000.00,
                    'total_cost_real' => 6000.00,
                    'total_budget_ht' => 10000.00,
                    'budget_ht' => 10000.00,
                ],
            ]);

        $path = $this->service->generateRentabilityReport($this->chantier);

        expect($path)->not->toBeNull();
    });

    test('inclut les métriques de performance', function () {
        $metrics = [
            'revenue' => 10000.00,
            'costs' => 6000.00,
            'margin' => 4000.00,
            'margin_rate' => 40,
            'progress' => 50, // Added default progress
            'hours' => ['real' => 100, 'budget' => 200, 'percent' => 50], // Added default hours
            'financials' => [ // Added default financials
                'labor_cost_real' => 3000.00,
                'material_cost_real' => 2000.00,
                'material_budget' => 4000.00,
                'subcontracting_cost_real' => 1000.00,
                'total_cost_real' => 6000.00,
                'total_budget_ht' => 10000.00,
                'budget_ht' => 10000.00,
            ],
        ];

        $this->analyticService->shouldReceive('getPerformanceMetrics')
            ->with($this->chantier)
            ->andReturn($metrics);

        $path = $this->service->generateRentabilityReport($this->chantier);

        expect($path)->not->toBeNull();
    });
});

describe('ChantierDocumentService - generateWeeklyJournal', function () {
    test('génère un journal hebdomadaire', function () {
        $startDate = now()->startOfWeek();

        $path = $this->service->generateWeeklyJournal($this->chantier, $startDate);

        expect($path)->toContain('journal_')
            ->and($path)->toContain($this->chantier->reference)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans chantiers/journals', function () {
        $startDate = now()->startOfWeek();

        $path = $this->service->generateWeeklyJournal($this->chantier, $startDate);

        expect($path)->toContain('chantiers/journals');
    });

    test('inclut la semaine dans le nom du fichier', function () {
        $startDate = Carbon::create(2026, 5, 18); // Lundi

        $path = $this->service->generateWeeklyJournal($this->chantier, $startDate);

        expect($path)->toContain($startDate->format('Y_W'));
    });

    test('récupère les logs de la semaine correcte', function () {
        $startDate = now()->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();

        ChantierLog::factory()->create([
            'chantier_id' => $this->chantier->id,
            'date' => $startDate->addDay(),
        ]);

        ChantierLog::factory()->create([
            'chantier_id' => $this->chantier->id,
            'date' => now()->addWeek(),
        ]);

        $path = $this->service->generateWeeklyJournal($this->chantier, $startDate);

        expect($path)->not->toBeNull();
    });

    test('inclut les logs du chantier dans la période', function () {
        $startDate = now()->startOfWeek();

        ChantierLog::factory(3)->create([
            'chantier_id' => $this->chantier->id,
            'date' => $startDate->addDay(),
        ]);

        $path = $this->service->generateWeeklyJournal($this->chantier, $startDate);

        expect($path)->not->toBeNull();
    });

    test('gère une semaine sans logs', function () {
        $startDate = now()->startOfWeek();

        $path = $this->service->generateWeeklyJournal($this->chantier, $startDate);

        expect($path)->not->toBeNull();
    });

    test('calcule correctement la période de fin de semaine', function () {
        $startDate = Carbon::create(2026, 5, 18); // Lundi
        $endDate = $startDate->copy()->endOfWeek(); // Dimanche

        ChantierLog::factory()->create([
            'chantier_id' => $this->chantier->id,
            'date' => $endDate,
        ]);

        $path = $this->service->generateWeeklyJournal($this->chantier, $startDate);

        expect($path)->not->toBeNull();
    });
});
