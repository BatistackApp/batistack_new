<?php

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Observers\Articles\BarcodeObserver;
use App\Services\Articles\ItemService;

beforeEach(function () {
    // Désactiver le BarcodeObserver car il utilise des routes Filament qui ne sont pas chargées lors de ces tests
    Item::flushEventListeners(); // Supprimer tous les événements sur l'Item
    Item::boot(); // Relancer le boot pour recharger ceux déclarés autrement

    // Mais plus sûrement avec Laravel, on peut utiliser sans events
    // ou ignorer spécifiquement avec Event::fakeFor ou Item::unsetEventDispatcher()

    // Mais pour les Model Attributes avec #[ObservedBy] (PHP 8, Laravel 11+)
    // la façon la plus simple est d'utiliser le mutateur global ou Event::fake()
    $this->itemService = app(ItemService::class);
    $this->unit = Unit::factory()->create(['symbol' => 'u']);
    $this->vat = VatRate::factory()->create(['rate' => 20]);
});

/**
 * Test de la création de variantes.
 */
test('il peut créer une variante avec le suffixe de référence correct', function () {
    Item::withoutEvents(function () {
        $parent = Item::create([
            'reference' => 'TOLEP',
            'name' => 'Tole Plane',
            'type' => ItemType::STOCKABLE,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vat->id,
        ]);

        $variant = $this->itemService->createVariant($parent, 'R7016', [
            'name' => 'Tole Plane Anthracite',
            'parent_id' => $parent->id,
        ]);

        expect($variant->reference)->toBe('TOLEP_R7016')
            ->and($variant->parent_id)->toBe($parent->id);
    });
});

/**
 * Test du calcul de coût d'un ouvrage simple.
 */
test('il calcule correctement le coût de revient d\'un ouvrage incluant les pertes', function () {
    Item::withoutEvents(function () {
        // 1. Un article composant
        $material = Item::create([
            'reference' => 'MAT-01',
            'name' => 'Composant',
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 10.00, // 10€
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vat->id,
        ]);

        // 2. Un ouvrage
        $work = Item::create([
            'reference' => 'OUV-01',
            'name' => 'Ouvrage Test',
            'type' => ItemType::WORK,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vat->id,
        ]);

        // 3. Composition : 2 unités du matériel avec 10% de perte
        $work->components()->create([
            'child_item_id' => $material->id,
            'quantity' => 2,
            'loss_percentage' => 10,
        ]);

        $costs = $this->itemService->calculateDetailedCost($work);

        // Calcul attendu : (10€ * 2) * 1.10 = 22€
        // Coût de la perte : (10€ * 2) * 0.10 = 2€
        expect($costs['total_cost'])->toEqual(22.00)
            ->and($costs['loss_cost'])->toEqual(2.00);
    });
});

/**
 * Test de la récursivité des ouvrages.
 */
test('il peut calculer le coût d\'un ouvrage imbriqué dans un autre', function () {
    Item::withoutEvents(function () {
        $material = Item::factory()->create(['purchase_price' => 10, 'type' => ItemType::STOCKABLE]);

        $subWork = Item::factory()->create(['type' => ItemType::WORK]);
        $subWork->components()->create(['child_item_id' => $material->id, 'quantity' => 1, 'loss_percentage' => 0]);

        $mainWork = Item::factory()->create(['type' => ItemType::WORK]);
        $mainWork->components()->create(['child_item_id' => $subWork->id, 'quantity' => 2, 'loss_percentage' => 10]);

        $costs = $this->itemService->calculateDetailedCost($mainWork);

        // SubWork = 10€
        // MainWork = (10€ * 2) * 1.1 = 22€
        expect($costs['total_cost'])->toEqual(22.00);
    });
});

/**
 * Test de l'exception de récursivité infinie (profondeur > 5).
 */
test('il lance une exception si la profondeur dépasse 5 (récursivité infinie)', function () {
    Item::withoutEvents(function () {
        $work = Item::factory()->create(['type' => ItemType::WORK]);
        
        // On simule une boucle infinie en ajoutant l'ouvrage comme composant de lui-même
        $work->components()->create([
            'child_item_id' => $work->id, 
            'quantity' => 1, 
            'loss_percentage' => 0
        ]);

        expect(fn() => $this->itemService->calculateDetailedCost($work))
            ->toThrow(\App\Exceptions\Articles\ArticlesModuleException::class, "Profondeur maximale de l'ouvrage atteinte (Récursion infinie suspectée).");
    });
});

/**
 * Test du calcul de prix pour le client.
 */
test('il retourne le prix correct pour le client (getPricingForClient)', function () {
    Item::withoutEvents(function () {
        $material = Item::create([
            'reference' => 'MAT-CLIENT',
            'name' => 'Composant Client',
            'type' => ItemType::STOCKABLE,
            'purchase_price' => 10.00,
            'unit_id' => $this->unit->id,
            'vat_rate_id' => $this->vat->id,
        ]);

        $pricing = $this->itemService->getPricingForClient($material);

        expect($pricing)->toEqual(10.00);
    });
});
