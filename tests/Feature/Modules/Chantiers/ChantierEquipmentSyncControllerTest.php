<?php

use App\Http\Controllers\Api\ChantierEquipmentSyncController;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierEquipmentTracking;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\FixedAsset;
use App\Models\RH\Equipement;
use App\Models\RH\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $this->chantier = Chantier::factory()->create([
        'manager_id' => $this->employee->id,
    ]);
    $this->category = AssetCategory::factory()->create();
    $this->controller = new ChantierEquipmentSyncController;
});

describe('ChantierEquipmentSyncController - chantiers()', function () {

    test('retourne les chantiers accessibles par l\'employé connecté', function () {
        $request = Request::create('/api/chantier-equipment/chantiers', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->chantiers($request);

        expect($response->getStatusCode())->toBe(200);

        $data = $response->getData(true);
        expect($data['data'])->toBeArray();
    });

    test('retourne un tableau vide si pas d\'employé', function () {
        $userWithoutEmployee = User::factory()->create();
        $request = Request::create('/api/chantier-equipment/chantiers', 'GET');
        $request->setUserResolver(fn () => $userWithoutEmployee);

        $response = $this->controller->chantiers($request);

        expect($response->getStatusCode())->toBe(200);
        $data = $response->getData(true);
        expect($data['data'])->toBeArray()->toBeEmpty();
    });
});

describe('ChantierEquipmentSyncController - presence()', function () {

    test('retourne le matériel présent sur le chantier aujourd\'hui', function () {
        $asset = FixedAsset::factory()->create([
            'asset_category_id' => $this->category->id,
            'name' => 'Grue mobile',
        ]);

        ChantierEquipmentTracking::create([
            'chantier_id' => $this->chantier->id,
            'trackable_type' => FixedAsset::class,
            'trackable_id' => $asset->id,
            'scanned_by' => $this->user->id,
            'check_in_at' => now()->subHours(2),
        ]);

        $request = Request::create("/api/chantier-equipment/presence?chantier_id={$this->chantier->id}", 'GET');
        $response = $this->controller->presence($request);

        expect($response->getStatusCode())->toBe(200);
        $data = $response->getData(true);
        expect($data['data'])->toHaveCount(1)
            ->and($data['data'][0]['label'])->toBe('Grue mobile')
            ->and($data['data'][0]['is_out'])->toBeFalse();
    });

    test('n\'inclut pas le matériel déjà sorti', function () {
        $asset = FixedAsset::factory()->create([
            'asset_category_id' => $this->category->id,
        ]);

        ChantierEquipmentTracking::create([
            'chantier_id' => $this->chantier->id,
            'trackable_type' => FixedAsset::class,
            'trackable_id' => $asset->id,
            'scanned_by' => $this->user->id,
            'check_in_at' => now()->subDays(2),
            'check_out_at' => now()->subDay(),
        ]);

        $request = Request::create('/api/chantier-equipment/presence', 'GET');
        $response = $this->controller->presence($request);

        $data = $response->getData(true);
        expect($data['data'])->toHaveCount(0);
    });

    test('filtre par chantier_id si fourni', function () {
        $asset = FixedAsset::factory()->create([
            'asset_category_id' => $this->category->id,
        ]);

        $otherChantier = Chantier::factory()->create();

        ChantierEquipmentTracking::create([
            'chantier_id' => $this->chantier->id,
            'trackable_type' => FixedAsset::class,
            'trackable_id' => $asset->id,
            'scanned_by' => $this->user->id,
            'check_in_at' => now(),
        ]);

        $request = Request::create("/api/chantier-equipment/presence?chantier_id={$otherChantier->id}", 'GET');
        $response = $this->controller->presence($request);

        $data = $response->getData(true);
        expect($data['data'])->toHaveCount(0);
    });
});

describe('ChantierEquipmentSyncController - scan()', function () {

    test('check_in crée un nouveau tracking pour un FixedAsset', function () {
        $asset = FixedAsset::factory()->create([
            'asset_category_id' => $this->category->id,
            'qr_token' => 'FA-TEST12345678',
            'name' => 'Mini-pelle',
        ]);

        $request = Request::create('/api/chantier-equipment/scan', 'POST', [
            'qr_token' => 'FA-TEST12345678',
            'chantier_id' => $this->chantier->id,
            'action' => 'check_in',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->scan($request);

        expect($response->getStatusCode())->toBe(200);
        $data = $response->getData(true);
        expect($data['success'])->toBeTrue()
            ->and($data['action'])->toBe('check_in')
            ->and($data['label'])->toBe('Mini-pelle');

        $this->assertDatabaseHas('chantier_equipment_trackings', [
            'chantier_id' => $this->chantier->id,
            'trackable_type' => FixedAsset::class,
            'trackable_id' => $asset->id,
        ]);
    });

    test('check_in crée un tracking pour un Equipement par qr_token', function () {
        $equipement = Equipement::factory()->create([
            'qr_token' => 'EQ-TEST12345678',
            'label' => 'Perceuse sans fil',
        ]);

        $request = Request::create('/api/chantier-equipment/scan', 'POST', [
            'qr_token' => 'EQ-TEST12345678',
            'chantier_id' => $this->chantier->id,
            'action' => 'check_in',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->scan($request);

        expect($response->getStatusCode())->toBe(200);
        $data = $response->getData(true);
        expect($data['success'])->toBeTrue()
            ->and($data['action'])->toBe('check_in');
    });

    test('check_in résout par serial_number si qr_token échoue', function () {
        $equipement = Equipement::factory()->create([
            'serial_number' => 'SN-ABC123',
            'label' => 'Mètre laser',
        ]);

        $request = Request::create('/api/chantier-equipment/scan', 'POST', [
            'qr_token' => 'SN-ABC123',
            'chantier_id' => $this->chantier->id,
            'action' => 'check_in',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->scan($request);

        expect($response->getStatusCode())->toBe(200);
        $data = $response->getData(true);
        expect($data['success'])->toBeTrue();
    });

    test('check_in résout par barcode si qr_token échoue', function () {
        $equipement = Equipement::factory()->create([
            'barcode' => 'BAR-XYZ789',
            'label' => 'Niveau à bulle',
        ]);

        $request = Request::create('/api/chantier-equipment/scan', 'POST', [
            'qr_token' => 'BAR-XYZ789',
            'chantier_id' => $this->chantier->id,
            'action' => 'check_in',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->scan($request);

        expect($response->getStatusCode())->toBe(200);
    });

    test('check_in ferme automatiquement un tracking existant ouvert', function () {
        $asset = FixedAsset::factory()->create([
            'asset_category_id' => $this->category->id,
            'qr_token' => 'FA-AUTO123',
        ]);

        // Créer un tracking ouvert sur un autre chantier
        $otherChantier = Chantier::factory()->create();
        ChantierEquipmentTracking::create([
            'chantier_id' => $otherChantier->id,
            'trackable_type' => FixedAsset::class,
            'trackable_id' => $asset->id,
            'scanned_by' => $this->user->id,
            'check_in_at' => now()->subHours(4),
        ]);

        $request = Request::create('/api/chantier-equipment/scan', 'POST', [
            'qr_token' => 'FA-AUTO123',
            'chantier_id' => $this->chantier->id,
            'action' => 'check_in',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->scan($request);

        expect($response->getStatusCode())->toBe(200);

        // L'ancien tracking doit être fermé
        $closedTracking = ChantierEquipmentTracking::where('trackable_type', FixedAsset::class)
            ->where('trackable_id', $asset->id)
            ->where('chantier_id', $otherChantier->id)
            ->first();
        expect($closedTracking->check_out_at)->not->toBeNull();
    });

    test('check_out ferme le tracking ouvert et retourne le coût', function () {
        $asset = FixedAsset::factory()->create([
            'asset_category_id' => $this->category->id,
            'qr_token' => 'FA-OUT123',
            'daily_rate' => 250.00,
        ]);

        ChantierEquipmentTracking::create([
            'chantier_id' => $this->chantier->id,
            'trackable_type' => FixedAsset::class,
            'trackable_id' => $asset->id,
            'scanned_by' => $this->user->id,
            'check_in_at' => now()->subDays(2),
        ]);

        $request = Request::create('/api/chantier-equipment/scan', 'POST', [
            'qr_token' => 'FA-OUT123',
            'chantier_id' => $this->chantier->id,
            'action' => 'check_out',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->scan($request);

        expect($response->getStatusCode())->toBe(200);
        $data = $response->getData(true);
        expect($data['success'])->toBeTrue()
            ->and($data['action'])->toBe('check_out')
            ->and($data['duration_days'])->toBeGreaterThanOrEqual(2)
            ->and($data['cost'])->toBeGreaterThanOrEqual(500.00);

        $updatedTracking = ChantierEquipmentTracking::find($data['tracking_id']);
        expect($updatedTracking->check_out_at)->not->toBeNull();
    });

    test('check_out échoue si aucun tracking ouvert', function () {
        $asset = FixedAsset::factory()->create([
            'asset_category_id' => $this->category->id,
            'qr_token' => 'FA-NOSTAT123',
        ]);

        $request = Request::create('/api/chantier-equipment/scan', 'POST', [
            'qr_token' => 'FA-NOSTAT123',
            'chantier_id' => $this->chantier->id,
            'action' => 'check_out',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->scan($request);

        expect($response->getStatusCode())->toBe(404);
        $data = $response->getData(true);
        expect($data['success'])->toBeFalse()
            ->and($data['error'])->toContain('Aucune session ouverte');
    });

    test('scan échoue avec un code inconnu', function () {
        $request = Request::create('/api/chantier-equipment/scan', 'POST', [
            'qr_token' => 'INEXISTANT-999',
            'chantier_id' => $this->chantier->id,
            'action' => 'check_in',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->scan($request);

        expect($response->getStatusCode())->toBe(404);
        $data = $response->getData(true);
        expect($data['success'])->toBeFalse()
            ->and($data['error'])->toContain('introuvable');
    });

    test('check_in avec notes enregistre les notes', function () {
        $asset = FixedAsset::factory()->create([
            'asset_category_id' => $this->category->id,
            'qr_token' => 'FA-NOTES123',
        ]);

        $request = Request::create('/api/chantier-equipment/scan', 'POST', [
            'qr_token' => 'FA-NOTES123',
            'chantier_id' => $this->chantier->id,
            'action' => 'check_in',
            'notes' => 'État neuf, aucun dommage',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->scan($request);

        $this->assertDatabaseHas('chantier_equipment_trackings', [
            'trackable_type' => FixedAsset::class,
            'trackable_id' => $asset->id,
            'notes' => 'État neuf, aucun dommage',
        ]);
    });
});
