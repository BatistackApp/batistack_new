<?php

use App\Exceptions\Tiers\TiersModuleException;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\VigilanceService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake(); // Empêche les jobs d'être réellement dispatchés
    $this->vigilanceService = new VigilanceService;

    // Définit une valeur de configuration factice pour l'URL de l'API URSSAF
    Config::set('services.urssaf.verify_url', 'https://api.urssaf.fr/v1/attestations/vigilance');

    Storage::fake('local');
});

it('verifies a urssaf certificate successfully', function () {
    // Simule la façade Http pour retourner une réponse réussie
    Http::fake([
        'https://api.urssaf.fr/v1/attestations/vigilance/*' => Http::response([
            'is_valid' => true,
            'valid_until' => now()->addYear()->format('Y-m-d H:i:s'),
            'some_other_data' => '...',
        ], 200),
    ]);

    $siren = '123456789';
    $verificationCode = 'CODE123';

    $result = $this->vigilanceService->verifyUrssafCertificate($siren, $verificationCode);

    expect($result)->toBeArray()
        ->and($result['is_valid'])->toBeTrue()
        ->and($result['certified_at'])->not->toBeNull()
        ->and($result['period'])->not->toBeNull()
        ->and($result['raw_response'])->toBeArray();

    // Vérifie qu'une requête HTTP a été envoyée avec les bons paramètres
    Http::assertSent(function ($request) use ($siren, $verificationCode) {
        return $request->method() === 'GET' &&
            $request->url() === "https://api.urssaf.fr/v1/attestations/vigilance/{$siren}" &&
            $request->hasHeader('X-Verification-Code', $verificationCode);
    });
});

it('returns invalid if urssaf certificate verification fails (unsuccessful http response)', function () {
    // Simule la façade Http pour retourner une réponse non réussie (par exemple, 404)
    Http::fake([
        'https://api.urssaf.fr/v1/attestations/vigilance/*' => Http::response([], 404),
    ]);

    $siren = '123456789';
    $verificationCode = 'CODE123';

    $result = $this->vigilanceService->verifyUrssafCertificate($siren, $verificationCode);

    expect($result)->toBeArray()
        ->and($result['is_valid'])->toBeFalse()
        ->and($result)->toHaveKey('error')
        ->and($result['error'])->toEqual('Vérification API impossible');

    // Vérifie qu'une requête HTTP a été envoyée
    Http::assertSent(function ($request) use ($siren, $verificationCode) {
        return $request->method() === 'GET' &&
            $request->url() === "https://api.urssaf.fr/v1/attestations/vigilance/{$siren}" &&
            $request->hasHeader('X-Verification-Code', $verificationCode);
    });
});

it('throws TiersModuleException if an exception occurs during urssaf verification', function () {
    // Simule la façade Http pour lancer une RequestException
    Http::fake([
        'https://api.urssaf.fr/v1/attestations/vigilance/*' => function (Request $request) {
            throw new Exception('Simulated connection error');
        },
    ]);

    // Simule la façade Log pour vérifier que la méthode 'error' est appelée
    Log::shouldReceive('error')
        ->once()
        ->with('VigilanceService : Erreur verification URSSAF pour 123456789');

    $this->expectException(TiersModuleException::class);
    $this->expectExceptionMessage('VigilanceService : Erreur verification URSSAF pour 123456789');

    $siren = '123456789';
    $verificationCode = 'CODE123';

    $this->vigilanceService->verifyUrssafCertificate($siren, $verificationCode);
});

// --- Tests pour scanCompliance ---

it('returns compliant when all documents exist', function () {
    // Arrange: Crée un tiers et les fichiers factices attendus
    $thirdParty = ThirdParty::factory()->create();
    $path = 'third_parties/'.$thirdParty->id.'/documents/';

    Storage::disk('local')->put($path.'vigilance_attestation.pdf', 'dummy content');
    Storage::disk('local')->put($path.'decennale_insurance.pdf', 'dummy content');
    Storage::disk('local')->put($path.'kbis.pdf', 'dummy content');

    // Act: Lance l'analyse de conformité
    $results = $this->vigilanceService->scanCompliance($thirdParty);

    // Assert: Vérifie que le résultat est conforme et sans problème
    expect($results['compliant'])->toBeTrue()
        ->and($results['issues'])->toBeEmpty();
});

it('returns not compliant when a document is missing', function () {
    // Arrange: Crée un tiers mais oublie volontairement un des fichiers
    $thirdParty = ThirdParty::factory()->create();
    $path = 'third_parties/'.$thirdParty->id.'/documents/';

    Storage::disk('local')->put($path.'vigilance_attestation.pdf', 'dummy content');
    // Le fichier decennale_insurance.pdf est manquant
    Storage::disk('local')->put($path.'kbis.pdf', 'dummy content');

    // Act: Lance l'analyse
    $results = $this->vigilanceService->scanCompliance($thirdParty);

    // Assert: Vérifie que le résultat n'est pas conforme et que le problème est bien identifié
    expect($results['compliant'])->toBeFalse()
        ->and($results['issues'])->toHaveCount(1)
        ->and($results['issues'][0])->toBe('Assurance Décennale');
});

it('returns not compliant when all documents are missing', function () {
    // Arrange: Crée un tiers mais aucun fichier
    $thirdParty = ThirdParty::factory()->create();

    // Act: Lance l'analyse
    $results = $this->vigilanceService->scanCompliance($thirdParty);

    // Assert: Vérifie que le résultat n'est pas conforme et que tous les documents sont listés comme manquants
    expect($results['compliant'])->toBeFalse()
        ->and($results['issues'])->toEqual([
            'Attestation de Vigilance (URSSAF)',
            'Assurance Décennale',
            'Extrait Kbis',
        ]);
});
