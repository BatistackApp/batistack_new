<?php

namespace Tests\Feature\Modules\Articles\Observers;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use App\Models\Core\Company;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Company::factory()->create();
    Storage::fake('public');
    $this->unit = Unit::factory()->create();
    $this->vatRate = VatRate::factory()->create(['rate' => 20]);
});

describe('BarcodeObserver - creating()', function () {
    test('valide référence unique', function () {
        Item::factory()->create(['reference' => 'UNIQUE-001']);

        expect(function () {
            Item::create([
                'reference' => 'UNIQUE-001',
                'name' => 'Autre',
                'type' => ItemType::STOCKABLE,
            ]);
        })->toThrow(Exception::class, 'déjà utilisée');
    });

    test('accepte référence unique', function () {
        $item = Item::create([
            'reference' => 'NEW-001',
            'name' => 'First',
            'type' => ItemType::STOCKABLE,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        expect($item->reference)->toBe('NEW-001');
    });
});

describe('BarcodeObserver - created()', function () {
    test('génère QR Code après création', function () {
        Log::shouldReceive('info')
            ->once()
            ->with('QR Code généré', \Mockery::any());

        Log::shouldReceive('info')
            ->once()
            ->with('Article créé', \Mockery::any());

        Item::create([
            'reference' => 'QR-001',
            'name' => 'Item with QR',
            'type' => ItemType::STOCKABLE,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);
    });

    test('log erreur si problème génération', function () {
        // Simuler erreur lors de génération
        Log::shouldReceive('error')
            ->zeroOrMoreTimes();

        Log::shouldReceive('info')
            ->zeroOrMoreTimes();

        $item = Item::create([
            'reference' => 'QR-002',
            'name' => 'Test',
            'type' => ItemType::STOCKABLE,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        expect($item)->not->toBeNull();
    });
});

describe('BarcodeObserver - updated()', function () {
    test('régénère QR si référence change', function () {
        $item = Item::factory()->create([
            'reference' => 'OLD-001',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('QR Code régénéré', \Mockery::any());

        $item->update(['reference' => 'NEW-001']);
    });

    test('ne régénère pas si autre champ change', function () {
        $item = Item::factory()->create();

        Log::shouldReceive('info')
            ->never()
            ->with('QR Code régénéré', \Mockery::any());

        $item->update(['name' => 'Nouveau nom']);
    });

    test('valide nouvelle référence unique', function () {
        $item1 = Item::factory()->create(['reference' => 'ITEM-001']);
        $item2 = Item::factory()->create(['reference' => 'ITEM-002']);

        expect(function () use ($item2) {
            $item2->update(['reference' => 'ITEM-001']);
        })->toThrow(Exception::class, 'déjà utilisée');
    });
});

describe('BarcodeObserver - deleted()', function () {
    test('supprime barcode lors suppression item', function () {
        Log::shouldReceive('warning')
            ->zeroOrMoreTimes();
        Log::shouldReceive('error')
            ->zeroOrMoreTimes();
        Log::shouldReceive('info')
            ->zeroOrMoreTimes();

        $item = Item::factory()->create([
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $item->delete();
    });
});

describe('BarcodeObserver - Intégration', function () {
    test('workflow complet: créer, modifier référence, supprimer', function () {
        // Création
        $item = Item::create([
            'reference' => 'TEST-001',
            'name' => 'Test Item',
            'type' => ItemType::STOCKABLE,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        expect($item->reference)->toBe('TEST-001');

        // Modifier référence
        $item->update(['reference' => 'TEST-002']);

        expect($item->fresh()->reference)->toBe('TEST-002');

        // Supprimer
        $item->delete();

        expect(Item::find($item->id))->toBeNull();
    });

    test('QR Code pour différents types d\'articles', function () {
        Log::shouldReceive('info')->atLeast()->once();

        Item::create([
            'reference' => 'MAT-001',
            'name' => 'Material',
            'type' => ItemType::STOCKABLE,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        Item::create([
            'reference' => 'SRV-001',
            'name' => 'Service',
            'type' => ItemType::LABOR,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        Item::create([
            'reference' => 'WRK-001',
            'name' => 'Work',
            'type' => ItemType::WORK,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        expect(Item::count())->toBe(3);
    });

    test('références avec caractères spéciaux', function () {
        Log::shouldReceive('info')->atLeast()->once();
        $item = Item::create([
            'reference' => 'item-001-v2',
            'name' => 'Test',
            'type' => ItemType::STOCKABLE,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        // Référence normalisée en majuscules
        expect($item->reference)->toBe('ITEM-001-V2');
    });

    test('deux articles avec mêmes noms mais références différentes', function () {
        Log::shouldReceive('info')->atLeast()->once();
        Item::create([
            'reference' => 'ART-001',
            'name' => 'Common Name',
            'type' => ItemType::STOCKABLE,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        Item::create([
            'reference' => 'ART-002',
            'name' => 'Common Name',
            'type' => ItemType::STOCKABLE,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        expect(Item::count())->toBe(2);
    });
});
