<?php

namespace Tests\Feature\Modules\Commerce;

use App\Enums\Articles\ItemType;
use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerDeliveryNoteItem;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerOrderItem;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\DeliveryNoteService;

beforeEach(function () {
    Company::factory()->create();
    $this->service = app(DeliveryNoteService::class);
    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();
});

describe('DeliveryNoteService - delivery', function () {
    test('marque la commande comme livrée', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $item = Item::factory()->create(['type' => ItemType::STOCKABLE]);
        CustomerOrderItem::factory()->create([
            'customer_order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => 10.0,
        ]);

        $deliveryNote = CustomerDeliveryNote::factory()->create([
            'customer_order_id' => $order->id,
            'status' => DeliveryStatus::PREPARATION,
        ]);

        CustomerDeliveryNoteItem::factory()->create([
            'customer_delivery_note_id' => $deliveryNote->id,
            'item_id' => $item->id,
            'quantity_delivered' => 10.0,
        ]);

        $result = $this->service->delivery($deliveryNote);

        expect($result->id)->toBe($deliveryNote->id)
            ->and($order->fresh()->status)->toBe(OrderStatus::DELIVERED);
    });

    test('marque la commande comme partiellement livrée', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $item = Item::factory()->create(['type' => ItemType::STOCKABLE]);
        CustomerOrderItem::factory()->create([
            'customer_order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => 10.0,
        ]);

        $deliveryNote = CustomerDeliveryNote::factory()->create([
            'customer_order_id' => $order->id,
            'status' => DeliveryStatus::PREPARATION,
        ]);

        CustomerDeliveryNoteItem::factory()->create([
            'customer_delivery_note_id' => $deliveryNote->id,
            'item_id' => $item->id,
            'quantity_delivered' => 5.0,
        ]);

        $result = $this->service->delivery($deliveryNote);

        expect($order->fresh()->status)->toBe(OrderStatus::PARTIALLY_DELIVERED);
    });

    test('additionne les quantités de plusieurs bons de livraison', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $item = Item::factory()->create(['type' => ItemType::STOCKABLE]);
        CustomerOrderItem::factory()->create([
            'customer_order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => 10.0,
        ]);

        // Premier bon de livraison: 7 unités
        $deliveryNote1 = CustomerDeliveryNote::factory()->create([
            'customer_order_id' => $order->id,
            'status' => DeliveryStatus::DELIVERED,
        ]);
        CustomerDeliveryNoteItem::factory()->create([
            'customer_delivery_note_id' => $deliveryNote1->id,
            'item_id' => $item->id,
            'quantity_delivered' => 7.0,
        ]);

        // Deuxième bon de livraison: 3 unités
        $deliveryNote2 = CustomerDeliveryNote::factory()->create([
            'customer_order_id' => $order->id,
            'status' => DeliveryStatus::PREPARATION,
        ]);
        CustomerDeliveryNoteItem::factory()->create([
            'customer_delivery_note_id' => $deliveryNote2->id,
            'item_id' => $item->id,
            'quantity_delivered' => 3.0,
        ]);

        $this->service->delivery($deliveryNote2);

        expect($order->fresh()->status)->toBe(OrderStatus::DELIVERED);
    });

    test('ignore les articles non stockables', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $serviceItem = Item::factory()->create(['type' => ItemType::WORK]);
        CustomerOrderItem::factory()->create([
            'customer_order_id' => $order->id,
            'item_id' => $serviceItem->id,
            'quantity' => 5.0,
        ]);

        $deliveryNote = CustomerDeliveryNote::factory()->create([
            'customer_order_id' => $order->id,
            'status' => DeliveryStatus::PREPARATION,
        ]);

        $this->service->delivery($deliveryNote);

        expect($order->fresh()->status)->toBe(OrderStatus::CONFIRMED);
    });

    test('gère une commande sans articles', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $deliveryNote = CustomerDeliveryNote::factory()->create([
            'customer_order_id' => $order->id,
            'status' => DeliveryStatus::PREPARATION,
        ]);

        $result = $this->service->delivery($deliveryNote);

        expect($result->id)->toBe($deliveryNote->id)
            ->and($order->fresh()->status)->toBe(OrderStatus::CONFIRMED);
    });

    test('gère un bon de livraison sans commande', function () {
        $deliveryNote = CustomerDeliveryNote::factory()->create([
            'customer_order_id' => null,
            'status' => DeliveryStatus::PREPARATION,
        ]);

        $result = $this->service->delivery($deliveryNote);

        expect($result->id)->toBe($deliveryNote->id);
    });

    test('ne modifie pas le statut si déjà livré', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'status' => OrderStatus::DELIVERED,
        ]);

        $item = Item::factory()->create(['type' => ItemType::STOCKABLE]);
        CustomerOrderItem::factory()->create([
            'customer_order_id' => $order->id,
            'item_id' => $item->id,
            'quantity' => 10.0,
        ]);

        $deliveryNote = CustomerDeliveryNote::factory()->create([
            'customer_order_id' => $order->id,
            'status' => DeliveryStatus::PREPARATION,
        ]);

        CustomerDeliveryNoteItem::factory()->create([
            'customer_delivery_note_id' => $deliveryNote->id,
            'item_id' => $item->id,
            'quantity_delivered' => 10.0,
        ]);

        $this->service->delivery($deliveryNote);

        expect($order->fresh()->status)->toBe(OrderStatus::DELIVERED);
    });

    test('charge les relations nécessaires', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
        ]);

        $deliveryNote = CustomerDeliveryNote::factory()->create([
            'customer_order_id' => $order->id,
        ]);

        $result = $this->service->delivery($deliveryNote);

        expect($result->id)->toBe($deliveryNote->id);
    });
});

describe('DeliveryNoteService - generateReference', function () {
    test('génère une référence unique', function () {
        $reference = $this->service->generateReference();

        expect($reference)->toMatch('/^BL-\d{4}-\d{3}$/');
    });

    test('commence par BL-YYYY-001 pour la première', function () {
        $reference = $this->service->generateReference();

        expect($reference)->toBe('BL-'.now()->year.'-001');
    });

    test('incrémente le numéro séquenciel', function () {
        $year = now()->year;

        CustomerDeliveryNote::factory()->create([
            'reference' => "BL-{$year}-001",
        ]);

        $reference = $this->service->generateReference();

        expect($reference)->toBe("BL-{$year}-002");
    });

    test('génère des références consécutives', function () {
        $year = now()->year;

        CustomerDeliveryNote::factory()->create(['reference' => "BL-{$year}-001"]);
        CustomerDeliveryNote::factory()->create(['reference' => "BL-{$year}-002"]);
        CustomerDeliveryNote::factory()->create(['reference' => "BL-{$year}-003"]);

        $reference = $this->service->generateReference();

        expect($reference)->toBe("BL-{$year}-004");
    });

    test('réinitialise à 001 pour une nouvelle année', function () {
        $lastYear = now()->subYear()->year;

        CustomerDeliveryNote::factory()->create([
            'reference' => "BL-{$lastYear}-999",
        ]);

        $reference = $this->service->generateReference();

        expect($reference)->toBe('BL-'.now()->year.'-001');
    });

    test('formate avec zéros à gauche', function () {
        $year = now()->year;

        CustomerDeliveryNote::factory()->create(['reference' => "BL-{$year}-001"]);
        CustomerDeliveryNote::factory()->create(['reference' => "BL-{$year}-009"]);

        $reference = $this->service->generateReference();

        expect($reference)->toBe("BL-{$year}-010");
    });

    test('gère les références non standards', function () {
        $year = now()->year;

        CustomerDeliveryNote::factory()->create(['reference' => "BL-{$year}-005"]);

        $reference = $this->service->generateReference();

        expect($reference)->toBe("BL-{$year}-006");
    });

    test('retourne une chaîne', function () {
        $reference = $this->service->generateReference();

        expect($reference)->toBeString();
    });
});
