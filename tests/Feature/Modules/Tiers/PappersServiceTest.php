<?php

use App\Enums\Tiers\LegalStatus;
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

it('detects a sauvegarde as legal_status', function () {
    $thirdParty = ThirdParty::factory()->create(['siren' => '111222333']);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [
                ['etat_administratif' => 'A', 'procedures_collectives' => 'Sauvegarde'],
            ],
        ], 200),
    ]);

    app(PappersService::class)->syncFinancialData($thirdParty);

    expect($thirdParty->refresh()->legal_status)->toBe(LegalStatus::SAUVEGARDE);
});

it('detects a redressement judiciaire as legal_status', function () {
    $thirdParty = ThirdParty::factory()->create(['siren' => '222333444']);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [
                ['etat_administratif' => 'A', 'procedures_collectives' => 'Redressement judiciaire'],
            ],
        ], 200),
    ]);

    app(PappersService::class)->syncFinancialData($thirdParty);

    expect($thirdParty->refresh()->legal_status)->toBe(LegalStatus::REDRESSEMENT_JUDICIAIRE);
});

it('detects a liquidation judiciaire as legal_status', function () {
    $thirdParty = ThirdParty::factory()->create(['siren' => '333444555']);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [
                ['etat_administratif' => 'A', 'procedures_collectives' => 'Liquidation judiciaire'],
            ],
        ], 200),
    ]);

    app(PappersService::class)->syncFinancialData($thirdParty);

    expect($thirdParty->refresh()->legal_status)->toBe(LegalStatus::LIQUIDATION_JUDICIAIRE);
});

it('detects a cessation as legal_status when there is no collective procedure', function () {
    $thirdParty = ThirdParty::factory()->create(['siren' => '444555666']);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [
                ['etat_administratif' => 'C', 'procedures_collectives' => 'Non'],
            ],
        ], 200),
    ]);

    app(PappersService::class)->syncFinancialData($thirdParty);

    expect($thirdParty->refresh()->legal_status)->toBe(LegalStatus::CESSATION);
});

it('treats an absent procedure field as no procedure (sain) when active', function () {
    $thirdParty = ThirdParty::factory()->create(['siren' => '555666777']);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [
                ['etat_administratif' => 'A'],
            ],
        ], 200),
    ]);

    app(PappersService::class)->syncFinancialData($thirdParty);

    expect($thirdParty->refresh()->legal_status)->toBe(LegalStatus::SAIN);
});

it('keeps legal_status unverified for an unknown procedure value', function () {
    $thirdParty = ThirdParty::factory()->create(['siren' => '666777888']);

    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response([
            'results' => [
                ['etat_administratif' => 'A', 'procedures_collectives' => 'Statut inconnu'],
            ],
        ], 200),
    ]);

    app(PappersService::class)->syncFinancialData($thirdParty);

    expect($thirdParty->refresh()->legal_status)->toBeNull();
});
