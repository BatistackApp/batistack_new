<?php

use App\Enums\Core\UnitType;
use App\Filament\Pages\ManageCompany;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Filament\Resources\Settings\SettingResource;
use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\VatRates\Pages\ListVatRates;
use App\Filament\Resources\VatRates\VatRateResource;
use App\Filament\Widgets\CoreStatsOverview;
use App\Filament\Widgets\SystemHealthWidget;
use App\Models\Core\Company;
use App\Models\Core\Setting;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\User;
use Livewire\Livewire;

/**
 * Configuration globale des tests
 */
beforeEach(function () {
    // Correction : Suppression de 'is_admin' car la colonne n'existe pas dans la migration users
    $this->admin = User::factory()->create();
    $this->actingAs($this->admin);
});

/**
 * TESTS DES RESOURCES (CRUD FILAMENT)
 */
describe('Resources Filament Core', function () {

    test('la ressource Unit peut lister les enregistrements', function () {
        Unit::factory()->count(3)->create();

        Livewire::test(ListUnits::class)
            ->assertCanSeeTableRecords(Unit::all())
            ->assertCanRenderTableColumn('name')
            ->assertCanRenderTableColumn('type');
    });

    test('la ressource Unit peut créer une nouvelle unité', function () {
        Livewire::test(CreateUnit::class)
            ->fillForm([
                'name' => 'Mètre linéaire',
                'symbol' => 'ml',
                'type' => UnitType::LENGTH,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('units', ['symbol' => 'ml']);
    });

    test('la ressource Setting affiche les groupes correctement', function () {
        Setting::factory()->create(['key' => 'google_maps_key', 'group' => 'api']);

        Livewire::test(ListSettings::class)
            ->assertCanSeeTableRecords(Setting::all())
            ->assertTableColumnStateSet('group', 'api', record: Setting::first());
    });

});

/**
 * TESTS DE LA PAGE PERSONNALISÉE (SINGLETON)
 */
describe('Page ManageCompany', function () {

    test('la page ManageCompany est accessible et pré-remplie', function () {
        $company = Company::factory()->create(['legal_name' => 'Batistack SARL']);

        Livewire::test(ManageCompany::class)
            ->assertFormSet([
                'legal_name' => 'Batistack SARL',
            ]);
    });

    test('la page ManageCompany peut mettre à jour les données', function () {
        Company::factory()->create();

        Livewire::test(ManageCompany::class)
            ->fillForm([
                'legal_name' => 'Nouvelle Raison Sociale',
                'city' => 'Paris',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('companies', ['legal_name' => 'Nouvelle Raison Sociale']);
    });

});

/**
 * TESTS DES WIDGETS DU TABLEAU DE BORD
 */
describe('Widgets Tableau de Bord Core', function () {

    test('le widget SystemHealth détecte les configurations manquantes', function () {
        // On s'assure qu'aucun paramètre n'est défini
        Setting::truncate();

        Livewire::test(SystemHealthWidget::class)
            ->assertSee('Google Maps API')
            // Le statut devrait être faux (danger color)
            ->assertDontSee('bg-success-100');
    });

});
