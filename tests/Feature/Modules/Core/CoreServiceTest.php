<?php

use App\Models\Core\Company;
use App\Models\Core\Setting;
use App\Models\Core\VatRate;
use App\Models\User;
use App\Notifications\Core\ConfigurationChangedNotification;
use App\Services\Core\CompanyService;
use App\Services\Core\DeviceDetectorService;
use App\Services\Core\GoogleMapsService;
use App\Services\Core\SettingService;
use App\Services\Core\VatService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Tests du Service Company
 */
test('le service company retourne l\'instance unique et la met en cache', function () {
    $company = Company::factory()->create(['legal_name' => 'Batistack Test']);
    $service = new CompanyService;

    // Premier appel (mise en cache)
    $result = $service->getCompany();
    expect($result->legal_name)->toBe('Batistack Test')
        ->and(Cache::has('core_company_singleton'))->toBeTrue();

    // Modification directe en base pour vérifier que le cache est utilisé
    Company::where('id', $company->id)->update(['legal_name' => 'Modifié']);
    expect($service->getCompany()->legal_name)->toBe('Batistack Test');
});

/**
 * Tests du Service Settings
 */
test('le service setting gère les valeurs et l\'invalidation du cache', function () {
    $service = new SettingService;

    // Test création et cache
    $service->set('app_theme', 'dark');
    expect($service->get('app_theme'))->toBe('dark');
    expect(Cache::has('core_setting_app_theme'))->toBeTrue();

    // Test modification via le service (l\'observer doit vider le cache)
    $service->set('app_theme', 'light');
    expect($service->get('app_theme'))->toBe('light');
});

/**
 * Tests du Service de TVA
 */
test('le service vat calcule correctement les montants TTC', function () {
    $vatRate = VatRate::create([
        'name' => 'TVA Test 20%',
        'rate' => 20.0000,
        'is_active' => true,
    ]);

    $service = new VatService;

    // 100€ HT + 20% TVA = 120€ TTC
    $total = $service->calculateTotal(100.00, $vatRate->id);
    expect($total)->toBe(120.00);

    // Test avec décimales complexes (ex: 5.5%)
    $vatRateReduced = VatRate::create([
        'name' => 'TVA 5.5%',
        'rate' => 5.5000,
        'is_active' => true,
    ]);
    $totalReduced = $service->calculateTotal(10.00, $vatRateReduced->id);
    expect($totalReduced)->toBe(10.55);
});

/**
 * Tests des Notifications
 */
test('une notification est envoyée lors d\'un changement critique', function () {
    Notification::fake();

    $user = User::factory()->create(['name' => 'Admin Test']);
    $notification = new ConfigurationChangedNotification('google_api_key', $user->name);

    // On simule l'envoi à l'utilisateur admin
    $user->notify($notification);

    Notification::assertSentTo(
        $user,
        ConfigurationChangedNotification::class,
        function ($notification, $channels) {
            return $notification->settingKey === 'google_api_key';
        }
    );
});

/**
 * Tests d'intégrité des Observers
 */
test('l\'observer company vide le cache lors d\'une mise à jour', function () {
    $company = Company::factory()->create();
    Cache::put('core_company_singleton', $company);

    $company->update(['legal_name' => 'Nouveau Nom']);

    expect(Cache::has('core_company_singleton'))->toBeFalse();
});

/**
 * TESTS DES SERVICES AUXILIAIRES
 */
describe('Service DeviceDetector', function () {

    test('il peut détecter un support mobile via user agent', function () {
        $request = request();
        $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1');

        $detector = new DeviceDetectorService($request);

        expect($detector->isMobile())->toBeTrue()
            ->and($detector->isDesktop())->toBeFalse();
    });

    test('il peut détecter un support desktop via user agent', function () {
        $request = request();
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $detector = new DeviceDetectorService($request);

        expect($detector->isDesktop())->toBeTrue()
            ->and($detector->isMobile())->toBeFalse();
    });
});

describe('Service GoogleMaps', function () {

    test('il peut géocoder une adresse', function () {
        Setting::factory()->create(['key' => 'google_maps_key', 'value' => 'fake_key']);

        Http::fake([
            'maps.googleapis.com/maps/api/geocode/*' => Http::response([
                'results' => [[
                    'geometry' => ['location' => ['lat' => 48.8566, 'lng' => 2.3522]],
                    'formatted_address' => 'Paris, France',
                ]],
            ], 200),
        ]);

        $service = app(GoogleMapsService::class);
        $result = $service->geocodeAddress('Paris');

        expect($result['lat'])->toBe(48.8566)
            ->and($result['formatted_address'])->toBe('Paris, France');
    });

    test('il peut calculer une distance entre deux points', function () {
        Setting::factory()->create(['key' => 'google_maps_key', 'value' => 'fake_key']);

        Http::fake([
            'maps.googleapis.com/maps/api/distancematrix/*' => Http::response([
                'rows' => [[
                    'elements' => [[
                        'status' => 'OK',
                        'distance' => ['text' => '10 km', 'value' => 10000],
                        'duration' => ['text' => '15 min', 'value' => 900],
                    ]],
                ]],
            ], 200),
        ]);

        $service = app(GoogleMapsService::class);
        $result = $service->getDistanceMatrix('Paris', 'Versailles');

        expect($result['distance_value'])->toBe(10000)
            ->and($result['duration_text'])->toBe('15 min');
    });
});
