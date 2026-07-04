<?php

namespace Tests\Feature\Modules\Articles;

use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use App\Services\Articles\InventoryService;

beforeEach(function () {
    $this->service = app(InventoryService::class);

    // Créer l'entreprise (nécessaire pour le PDF)
    Company::factory()->create([
        'legal_name' => 'Batistack Corp',
        'city' => 'Paris',
        'zip_code' => '75001',
    ]);
});

test('génère correctement la valorisation en CSV', function () {
    // Créer un entrepôt
    $warehouse = Warehouse::factory()->create(['name' => 'Dépôt Central']);

    // Créer deux articles
    $item1 = Item::factory()->create([
        'reference' => 'ART-001',
        'name' => 'Vis à bois',
        'purchase_price' => 0.50,
    ]);

    $item2 = Item::factory()->create([
        'reference' => 'ART-002',
        'name' => 'Planche chêne',
        'purchase_price' => 20.00,
    ]);

    // Assigner du stock
    Stock::create([
        'item_id' => $item1->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
        'min_threshold' => 5,
    ]);

    Stock::create([
        'item_id' => $item2->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 5,
        'min_threshold' => 2,
    ]);

    // Générer le CSV
    $csv = $this->service->generateValuationCsv();

    // Vérifications
    expect($csv)
        ->toContain('ART-001')
        ->toContain('ART-002')
        // 10 * 0.50 = 5.00
        ->toContain('"Vis à bois";"Dépôt Central";10.00;0.50;5.00')
        // 5 * 20 = 100.00
        ->toContain('"Planche chêne";"Dépôt Central";5.00;20.00;100.00');
});

test('génère correctement la valorisation en PDF', function () {
    // Créer un entrepôt et stock
    $warehouse = Warehouse::factory()->create(['name' => 'Dépôt Central']);
    $item = Item::factory()->create([
        'reference' => 'ART-001',
        'name' => 'Marteau',
        'purchase_price' => 15.00,
    ]);
    Stock::create([
        'item_id' => $item->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 2,
        'min_threshold' => 1,
    ]);

    // Générer le PDF
    $fullPath = $this->service->generateValuationPdf();

    // Vérifier que le fichier a été généré
    expect($fullPath)->toBeFile();

    // Nettoyage
    @unlink($fullPath);
});
