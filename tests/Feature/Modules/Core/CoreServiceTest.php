<?php

use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\User;
use App\Notifications\Core\ConfigurationChangedNotification;
use App\Services\Core\CompanyService;
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
