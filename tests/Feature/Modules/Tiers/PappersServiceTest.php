<?php

use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\PappersService;
use Illuminate\Support\Facades\Http;

it('synchronizes financial data successfully', function () {
    $thirdParty = ThirdParty::factory()->create([
        'siren' => '123456789',
    ]);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [
                [
                    'etat_administratif' => 'A',
                    'procedures_collectives' => 'Non',
                    'finances' => [
                        'chiffre_affaires' => 1000000,
                        'resultat_net' => 50000,
                        'annee_cloture_exercice' => '2025'
                    ]
                ]
            ]
        ], 200)
    ]);

    $service = app(PappersService::class);
    $result = $service->syncFinancialData($thirdParty);

    expect($result)->toBeTrue();
    
    $thirdParty->refresh();
    
    expect($thirdParty->financial_status)->toBe('Sain')
        ->and($thirdParty->financial_data['chiffre_affaires'])->toBe(1000000)
        ->and($thirdParty->last_financial_sync_at)->not->toBeNull();
});

it('detects cessation or liquidation', function () {
    $thirdParty = ThirdParty::factory()->create([
        'siren' => '987654321',
    ]);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [
                [
                    'etat_administratif' => 'C', // Cessation
                    'procedures_collectives' => 'Liquidation judiciaire',
                ]
            ]
        ], 200)
    ]);

    $service = app(PappersService::class);
    $service->syncFinancialData($thirdParty);

    $thirdParty->refresh();
    expect($thirdParty->financial_status)->toBe('Procédure Collective'); // Because 'procedures_collectives' is set
});

it('returns false if siren is missing', function () {
    $thirdParty = ThirdParty::factory()->create([
        'siren' => null,
        'siret' => null, // ensure both are null so it fails
    ]);

    Log::shouldReceive('warning')->once()->withArgs(function ($message) {
        return str_contains($message, 'Impossible de synchroniser: SIREN/SIRET manquant');
    });

    $service = app(PappersService::class);
    $result = $service->syncFinancialData($thirdParty);

    expect($result)->toBeFalse();
});

it('returns false if api finds no results', function () {
    $thirdParty = ThirdParty::factory()->create([
        'siren' => '000000000',
    ]);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => []
        ], 200)
    ]);

    Log::shouldReceive('warning')->once()->withArgs(function ($message) {
        return str_contains($message, "API recherche-entreprises n'a trouvé aucun résultat");
    });

    $service = app(PappersService::class);
    $result = $service->syncFinancialData($thirdParty);

    expect($result)->toBeFalse();
});

it('catches exception and returns false', function () {
    $thirdParty = ThirdParty::factory()->create([
        'siren' => '123456789',
    ]);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => function () {
            throw new \Exception('Connection timeout');
        }
    ]);

    Log::shouldReceive('error')->once()->withArgs(function ($message) {
        return str_contains($message, 'Erreur lors de la synchro financière pour le tiers');
    });

    $service = app(PappersService::class);
    $result = $service->syncFinancialData($thirdParty);

    expect($result)->toBeFalse();
});
