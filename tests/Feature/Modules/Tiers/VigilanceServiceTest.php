<?php

use App\Exceptions\Tiers\TiersModuleException;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\VigilanceService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Queue::fake(); // Empêche les jobs d'être réellement dispatchés
    $this->vigilanceService = new VigilanceService;

    // Définit une valeur de configuration factice pour l'URL de l'API URSSAF
    Config::set('services.urssaf.verify_url', 'https://api.urssaf.fr/v1/attestations/vigilance');
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
        'https://api.urssaf.fr/v1/attestations/vigilance/*' => function (\Illuminate\Http\Client\Request $request) {
            throw new \Exception('Simulated connection error');
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

it('scans compliance and finds no issues with all valid documents', function () {
    $thirdParty = ThirdParty::factory()->create();

    // Crée des mocks pour les objets Media et leurs propriétés
    $mockMediaVigilance = Mockery::mock(Media::class);
    $mockMediaVigilance->shouldReceive('getCustomProperty')->with('expires_at')->andReturn(now()->addMonth()->format('Y-m-d H:i:s'));

    $mockMediaDecennale = Mockery::mock(Media::class);
    $mockMediaDecennale->shouldReceive('getCustomProperty')->with('expires_at')->andReturn(now()->addYear()->format('Y-m-d H:i:s'));

    $mockMediaKbis = Mockery::mock(Media::class);
    $mockMediaKbis->shouldReceive('getCustomProperty')->with('expires_at')->andReturn(null); // Kbis souvent sans date d'expiration

    // Crée un mock partiel du modèle ThirdParty pour intercepter les appels à getFirstMedia
    $mockThirdParty = Mockery::mock($thirdParty)->makePartial();
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('vigilance_attestation')
        ->andReturn($mockMediaVigilance);
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('decennale_insurance')
        ->andReturn($mockMediaDecennale);
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('kbis')
        ->andReturn($mockMediaKbis);

    $results = $this->vigilanceService->scanCompliance($mockThirdParty);

    expect($results['compliant'])->toBeTrue()
        ->and($results['issues'])->toBeEmpty();
});

it('scans compliance and finds issues with missing documents', function () {
    $thirdParty = ThirdParty::factory()->create();

    // Seul le Kbis est présent et valide, les autres sont manquants
    $mockMediaKbis = Mockery::mock(Media::class);
    $mockMediaKbis->shouldReceive('getCustomProperty')->with('expires_at')->andReturn(null);

    $mockThirdParty = Mockery::mock($thirdParty)->makePartial();
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('vigilance_attestation')
        ->andReturn(null); // Manquant
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('decennale_insurance')
        ->andReturn(null); // Manquant
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('kbis')
        ->andReturn($mockMediaKbis);

    $results = $this->vigilanceService->scanCompliance($mockThirdParty);

    expect($results['compliant'])->toBeFalse()
        ->and($results['issues'])->toHaveCount(2)
        ->and($results['issues'])->toContain('Document manquant : Attestation de Vigilance (URSSAF)')
        ->and($results['issues'])->toContain('Document manquant : Assurance Décennale');
});

it('scans compliance and finds issues with expired documents', function () {
    $thirdParty = ThirdParty::factory()->create();

    // Crée des mocks Media, l'un d'eux est expiré
    $mockMediaVigilance = Mockery::mock(Media::class);
    $mockMediaVigilance->shouldReceive('getCustomProperty')->with('expires_at')->andReturn(now()->subDay()->format('Y-m-d H:i:s')); // Expiré

    $mockMediaDecennale = Mockery::mock(Media::class);
    $mockMediaDecennale->shouldReceive('getCustomProperty')->with('expires_at')->andReturn(now()->addYear()->format('Y-m-d H:i:s')); // Valide

    $mockMediaKbis = Mockery::mock(Media::class);
    $mockMediaKbis->shouldReceive('getCustomProperty')->with('expires_at')->andReturn(null); // Sans expiration

    $mockThirdParty = Mockery::mock($thirdParty)->makePartial();
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('vigilance_attestation')
        ->andReturn($mockMediaVigilance);
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('decennale_insurance')
        ->andReturn($mockMediaDecennale);
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('kbis')
        ->andReturn($mockMediaKbis);

    $results = $this->vigilanceService->scanCompliance($mockThirdParty);

    expect($results['compliant'])->toBeFalse()
        ->and($results['issues'])->toHaveCount(1)
        ->and($results['issues'])->toContain('Document expiré : Attestation de Vigilance (URSSAF) (le '.now()->subDay()->format('d/m/Y').')');
});

it('scans compliance and finds a mix of issues', function () {
    $thirdParty = ThirdParty::factory()->create();

    // Attestation de vigilance manquante, assurance décennale expirée, Kbis valide
    $mockMediaDecennale = Mockery::mock(Media::class);
    $mockMediaDecennale->shouldReceive('getCustomProperty')->with('expires_at')->andReturn(now()->subMonth()->format('Y-m-d H:i:s')); // Expirée

    $mockMediaKbis = Mockery::mock(Media::class);
    $mockMediaKbis->shouldReceive('getCustomProperty')->with('expires_at')->andReturn(now()->addYear()->format('Y-m-d H:i:s')); // Valide

    $mockThirdParty = Mockery::mock($thirdParty)->makePartial();
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('vigilance_attestation')
        ->andReturn(null); // Manquant
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('decennale_insurance')
        ->andReturn($mockMediaDecennale);
    $mockThirdParty->shouldReceive('getFirstMedia')
        ->with('kbis')
        ->andReturn($mockMediaKbis);

    $results = $this->vigilanceService->scanCompliance($mockThirdParty);

    expect($results['compliant'])->toBeFalse()
        ->and($results['issues'])->toHaveCount(2)
        ->and($results['issues'])->toContain('Document manquant : Attestation de Vigilance (URSSAF)')
        ->and($results['issues'])->toContain('Document expiré : Assurance Décennale (le '.now()->subMonth()->format('d/m/Y').')');
});
