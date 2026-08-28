<?php

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Flottes\VehicleType;
use App\Enums\Tiers\ThirdPartyType;
use App\Mail\Articles\SupplierQuoteRequestMail;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Articles\StockService;
use App\Services\Chantiers\ChantierAnalyticService;
use Illuminate\Support\Facades\Mail;

it('decreases chantier margin when materials are affected', function () {
    // 1. Arrange
    $chantier = Chantier::factory()->create([
        'budget_total_ht' => 5000,
    ]);

    $warehouse = Warehouse::factory()->create();

    $item = Item::factory()->create([
        'purchase_price' => 50, // 50€ l'unité
    ]);

    $stock = Stock::create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'quantity' => 10,
    ]);

    // 2. Act : On affecte 2 unités au chantier (via le StockService)
    $stockService = app(StockService::class);
    $stockService->exit(
        item: $item,
        warehouse: $warehouse,
        quantity: 2,
        reason: 'Affectation test',
        source: StockMouvementSource::SITE,
        referenceId: $chantier->id
    );

    // 3. Assert
    $analyticService = app(ChantierAnalyticService::class);
    $metrics = $analyticService->getPerformanceMetrics($chantier);

    // Coût matériel attendu : 2 unités * 50€ = 100€
    expect($metrics['financials']['material_cost_real'])->toBe(100.0);
    // Marge restante : Budget (5000) - Coût Matériel (100) = 4900€
    expect($metrics['financials']['margin_real'])->toBe(4900.0);
});

it('automatically creates a virtual warehouse when a utility vehicle is created', function () {
    // 1. Act : Création d'un VUL
    $vehicle = Vehicle::factory()->create([
        'type' => VehicleType::UTILITY,
        'license_plate' => 'AB-123-CD',
    ]);

    // 2. Assert : Un entrepôt virtuel a été créé
    $warehouse = Warehouse::where('vehicle_id', $vehicle->id)->first();

    expect($warehouse)->not->toBeNull()
        ->and($warehouse->name)->toBe('Camionnette AB123CD') // The observer removes dashes
        ->and($warehouse->is_active)->toBeTrue();
});

it('does not create a warehouse for non-utility vehicles', function () {
    // 1. Act : Création d'une voiture de fonction (PASSENGER)
    $vehicle = Vehicle::factory()->create([
        'type' => VehicleType::PASSENGER,
        'license_plate' => 'XY-999-ZZ',
    ]);

    // 2. Assert : Aucun entrepôt n'a été créé
    $warehouse = Warehouse::where('vehicle_id', $vehicle->id)->first();
    expect($warehouse)->toBeNull();
});

it('can send a quote request email to the item supplier', function () {
    Mail::fake();

    // 1. Arrange : Un fournisseur et un article
    $supplier = ThirdParty::factory()->create([
        'name' => 'BricoDépôt',
        'email' => 'contact@bricodepot.test',
        'type' => ThirdPartyType::SUPPLIER,
    ]);

    $item = Item::factory()->create([
        'name' => 'Sac de Ciment',
        'supplier_id' => $supplier->id,
    ]);

    $user = User::factory()->create();

    // 2. Act : On tape l'endpoint de demande de prix (Filament action)
    $response = $this->actingAs($user)
        ->get(route('articles.request-quote', ['item' => $item->id]));

    // 3. Assert
    $response->assertSessionHasNoErrors();
    $response->assertSessionMissing('error');

    Mail::assertQueued(SupplierQuoteRequestMail::class, function (SupplierQuoteRequestMail $mail) use ($supplier, $item) {
        return $mail->hasTo($supplier->email) &&
               $mail->item->id === $item->id;
    });
});
