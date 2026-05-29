<?php

namespace Tests\Feature\Modules\Core;

use App\Models\Core\Company;
use App\Services\Core\SettingService;
use App\Services\Core\SirenService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

beforeEach(function () {
    Company::factory()->create();
    Cache::flush();

    $this->settingService = Mockery::mock(SettingService::class);
    $this->settingService->shouldReceive('get')
        ->with('siren_api_key')
        ->andReturn('test-api-key');

    $this->service = new SirenService($this->settingService);
});

describe('SirenService - isValid', function () {
    test('valide un SIREN correct', function () {
        $siren = '732829320'; // SIREN valide

        expect($this->service->isValid($siren))->toBeTrue();
    });

    test('valide un SIRET correct', function () {
        $siret = '73282932000074'; // SIRET valide

        expect($this->service->isValid($siret))->toBeTrue();
    });

    test('rejette un SIREN avec format invalide', function () {
        expect($this->service->isValid('12345'))->toBeFalse()
            ->and($this->service->isValid('12345678901234567'))->toBeFalse()
            ->and($this->service->isValid('abc123def'))->toBeFalse();
    });

    test('accepte les espaces', function () {
        $siren = '732 829 320';

        expect($this->service->isValid($siren))->toBeTrue();
    });

    test('valide avec l\'algorithme de Luhn', function () {
        $validSiren = '732829320';
        $invalidSiren = '732829321';

        expect($this->service->isValid($validSiren))->toBeTrue()
            ->and($this->service->isValid($invalidSiren))->toBeFalse();
    });

    test('rejette les numéros avec caractères spéciaux', function () {
        expect($this->service->isValid('732-829-320'))->toBeFalse()
            ->and($this->service->isValid('732.829.320'))->toBeFalse();
    });

    test('gère les zéros en début', function () {
        // SIREN commençant par 0
        $siren = '002123456';

        $result = $this->service->isValid($siren);

        expect($result)->toBeBool();
    });
});

describe('SirenService - exists', function () {
    test('retourne true si l\'API répond avec succès', function () {
        Http::fake([
            'api.insee.fr/*' => Http::response(['siret' => '73282932000074'], 200),
        ]);

        $result = $this->service->exists('732829320');

        expect($result)->toBeTrue();
    });

    test('retourne false si l\'API retourne 404', function () {
        Http::fake([
            'api.insee.fr/*' => Http::response(null, 404),
        ]);

        $result = $this->service->exists('999999999');

        expect($result)->toBeFalse();
    });

    test('retourne false pour un format invalide', function () {
        $result = $this->service->exists('12345');

        expect($result)->toBeFalse();
    });

    test('met en cache le résultat', function () {
        Http::fake([
            'api.insee.fr/*' => Http::response(['siret' => '73282932000074'], 200),
        ]);

        $siren = '732829320';

        $this->service->exists($siren);

        $cached = Cache::get('insee_exists_732829320');

        expect($cached)->toBeTrue();
    });

    test('utilise le cache pour les appels suivants', function () {
        Http::fake([
            'api.insee.fr/*' => Http::response(['siret' => '73282932000074'], 200),
        ]);

        $siren = '732829320';

        $this->service->exists($siren);
        Http::assertSent(function () {
            return true;
        });

        Http::fake([
            'api.insee.fr/*' => Http::response(null, 500),
        ]);

        $result = $this->service->exists($siren);

        expect($result)->toBeTrue();
    });

    test('détecte un SIREN vs SIRET', function () {
        Http::fake([
            '*siren/*' => Http::response(['siren' => '732829320'], 200),
            '*siret/*' => Http::response(['siret' => '73282932000074'], 200),
        ]);

        $sirenResult = $this->service->exists('732829320');
        $siretResult = $this->service->exists('73282932000074');

        expect($sirenResult)->toBeTrue()
            ->and($siretResult)->toBeTrue();
    });

    test('gère les espaces dans l\'identifiant', function () {
        Http::fake([
            'api.insee.fr/*' => Http::response(['siren' => '732829320'], 200),
        ]);

        $result = $this->service->exists('732 829 320');

        expect($result)->toBeTrue();
    });
});

describe('SirenService - getInformation', function () {
    test('retourne les informations complètes', function () {
        $data = [
            'siren' => '732829320',
            'etablissements' => [
                [
                    'siret' => '73282932000074',
                    'denomination' => 'ACME Corp',
                ],
            ],
        ];

        Http::fake([
            'api.insee.fr/*' => Http::response($data, 200),
        ]);

        $result = $this->service->getInformation('732829320');

        expect($result)->toBe($data);
    });

    test('retourne null si 404', function () {
        Http::fake([
            'api.insee.fr/*' => Http::response(null, 404),
        ]);

        $result = $this->service->getInformation('999999999');

        expect($result)->toBeNull();
    });

    test('lève une exception si 401', function () {
        Http::fake([
            'api.insee.fr/*' => Http::response(null, 401),
        ]);

        expect(function () {
            $this->service->getInformation('732829320');
        })->toThrow(\Exception::class, 'non autorisé');
    });

    test('retourne null pour un format invalide', function () {
        $result = $this->service->getInformation('12345');

        expect($result)->toBeNull();
    });

    test('log une erreur en cas d\'exception', function () {
        Log::shouldReceive('warning')->once();

        Http::fake([
            'api.insee.fr/*' => Http::response(null, 500),
        ]);

        $result = $this->service->getInformation('732829320');
        expect($result)->toBeNull();
    });

    test('gère les erreurs HTTP inhabituelles', function () {
        Log::shouldReceive('warning')->once();

        Http::fake([
            'api.insee.fr/*' => Http::response(null, 500),
        ]);

        $result = $this->service->getInformation('732829320');
        expect($result)->toBeNull();
    });

    test('différencie SIREN et SIRET', function () {
        Http::fake([
            '*siren/*' => Http::response(['siren' => '732829320'], 200),
            '*siret/*' => Http::response(['siret' => '73282932000074'], 200),
        ]);

        $sirenResult = $this->service->getInformation('732829320');
        $siretResult = $this->service->getInformation('73282932000074');

        expect($sirenResult['siren'])->toBe('732829320')
            ->and($siretResult['siret'])->toBe('73282932000074');
    });

    test('nettoie les espaces de l\'identifiant', function () {
        Http::fake([
            'api.insee.fr/*' => Http::response(['siren' => '732829320'], 200),
        ]);

        $result = $this->service->getInformation('732 829 320');

        expect($result)->not->toBeNull();
    });
});
