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
