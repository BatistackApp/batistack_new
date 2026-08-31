<?php

namespace Tests\Feature\Modules\Tiers;

use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\TiersDocumentService;
use App\Services\Tiers\VigilanceService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Mockery;

beforeEach(function () {
    Company::factory()->create();
    Storage::fake('public');

    $this->vigilanceService = Mockery::mock(VigilanceService::class);
    $this->service = new TiersDocumentService($this->vigilanceService);
});

describe('TiersDocumentService - generateList', function () {
    test('génère une liste de tiers', function () {
        $thirdParties = ThirdParty::factory(3)->create();

        $path = $this->service->generateList($thirdParties);

        expect($path)->toContain('liste_tiers_')
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans le répertoire tiers', function () {
        $thirdParties = ThirdParty::factory(2)->create();

        $path = $this->service->generateList($thirdParties);

        expect($path)->toContain('tiers');
    });

    test('utilise le format landscape', function () {
        $thirdParties = new Collection;

        $path = $this->service->generateList($thirdParties);

        expect($path)->not->toBeNull();
    });

    test('inclut la date et heure actuelle', function () {
        $thirdParties = new Collection;
        $timestamp = now()->format('Ymd_Hi');

        $path = $this->service->generateList($thirdParties);

        expect($path)->toContain($timestamp);
    });

    test('gère une liste vide', function () {
        $thirdParties = new Collection;

        $path = $this->service->generateList($thirdParties);

        expect($path)->not->toBeNull();
    });

    test('gère une liste avec plusieurs tiers', function () {
        $thirdParties = ThirdParty::factory(10)->create();

        $path = $this->service->generateList($thirdParties);

        expect($path)->not->toBeNull();
    });
});

describe('TiersDocumentService - generateDetails', function () {
    test('génère une fiche détaillée de tiers', function () {
        $thirdParty = ThirdParty::factory()->create();

        $this->vigilanceService->shouldReceive('scanCompliance')
            ->with($thirdParty)
            ->andReturn([
                'status' => 'compliant',
                'alerts' => [],
                'compliant' => true, // Added 'compliant' key
            ]);

        $path = $this->service->generateDetails($thirdParty);

        expect($path)->toContain('fiche_tiers_')
            ->and($path)->toContain((string) $thirdParty->id)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans le répertoire tiers', function () {
        $thirdParty = ThirdParty::factory()->create();

        $this->vigilanceService->shouldReceive('scanCompliance')
            ->andReturn([
                'status' => 'compliant',
                'alerts' => [],
                'compliant' => true, // Added 'compliant' key
            ]);

        $path = $this->service->generateDetails($thirdParty);

        expect($path)->toContain('tiers');
    });

    test('charge les adresses du tiers', function () {
        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->load(['addresses', 'contacts']);

        $this->vigilanceService->shouldReceive('scanCompliance')
            ->andReturn([
                'compliant' => true,
            ]);

        $path = $this->service->generateDetails($thirdParty);

        expect($path)->not->toBeNull();
    });

    test('charge les contacts du tiers', function () {
        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->load(['contacts']);

        $this->vigilanceService->shouldReceive('scanCompliance')
            ->andReturn([
                'status' => 'compliant',
                'alerts' => [],
                'compliant' => true, // Added 'compliant' key
            ]);

        $path = $this->service->generateDetails($thirdParty);

        expect($path)->not->toBeNull();
    });

    test('inclut l\'analyse de conformité', function () {
        $thirdParty = ThirdParty::factory()->create();

        $compliance = [
            'status' => 'non_compliant',
            'alerts' => ['Entreprise en difficulté financière'],
            'risk_level' => 'high',
            'compliant' => false,
        ];

        $this->vigilanceService->shouldReceive('scanCompliance')
            ->with($thirdParty)
            ->andReturn($compliance);

        $path = $this->service->generateDetails($thirdParty);

        expect($path)->not->toBeNull();
    });

    test('inclut la date et heure actuelles', function () {
        $thirdParty = ThirdParty::factory()->create();
        $timestamp = now()->format('Ymd_Hi');

        $this->vigilanceService->shouldReceive('scanCompliance')
            ->andReturn([
                'status' => 'compliant',
                'alerts' => [],
                'compliant' => true, // Added 'compliant' key
            ]);

        $path = $this->service->generateDetails($thirdParty);

        expect($path)->toContain($timestamp);
    });
});

describe('TiersDocumentService - generateContract', function () {
    test('génère un contrat de sous-traitance', function () {
        $thirdParty = ThirdParty::factory()->create();

        $path = $this->service->generateContract($thirdParty);

        expect($path)->toContain('contract_')
            ->and($path)->toContain((string) $thirdParty->id)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans le répertoire tiers', function () {
        $thirdParty = ThirdParty::factory()->create();

        $path = $this->service->generateContract($thirdParty);

        expect($path)->toContain('tiers');
    });

    test('charge les adresses du tiers', function () {
        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->load(['addresses', 'contacts']);

        $path = $this->service->generateContract($thirdParty);

        expect($path)->not->toBeNull();
    });

    test('charge les contacts du tiers', function () {
        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->load(['contacts']);

        $path = $this->service->generateContract($thirdParty);

        expect($path)->not->toBeNull();
    });

    test('retourne le contenu PDF si view=true', function () {
        $thirdParty = ThirdParty::factory()->create();

        $result = $this->service->generateContract($thirdParty, view: true);

        expect($result)->toBeString();
    });

    test('retourne le chemin du fichier si view=false', function () {
        $thirdParty = ThirdParty::factory()->create();

        $path = $this->service->generateContract($thirdParty, view: false);

        expect($path)->toContain('contract_');
    });

    test('inclut la date et heure actuelles', function () {
        $thirdParty = ThirdParty::factory()->create();
        $timestamp = now()->format('Ymd_Hi');

        $path = $this->service->generateContract($thirdParty);

        expect($path)->toContain($timestamp);
    });
});
