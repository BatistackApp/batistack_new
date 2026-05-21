<?php

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerCreditNote;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\CustomerOrderService;

beforeEach(function () {
    \App\Models\Core\Company::factory()->create();
    $this->orderService = app(CustomerOrderService::class);

    // Données de base
    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->vatRate = VatRate::factory()->create(['is_default' => true, 'rate' => 20.00]);
    $this->item = Item::factory()->create();
    $this->responsable = User::factory()->create();

    // Création d'une commande test
    $this->order = CustomerOrder::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->responsable->id,
        'status' => OrderStatus::CONFIRMED,
        'total_ht' => 5000.00,
        'total_ttc' => 6000.00,
    ]);

    // Ajout de lignes à la commande
    $this->order->items()->create([
        'item_id' => $this->item->id,
        'name' => 'Composant Test',
        'quantity' => 100,
        'selling_price' => 50.00,
        'vat_rate_id' => $this->vatRate->id,
    ]);

    Queue::fake();
    Notification::fake();
});

describe('CustomerOrderService - Bons de livraison', function () {

    test('crée un bon de livraison avec les articles livrés', function () {
        $bl = $this->orderService->createDeliveryNote($this->order, [
            [
                'item_id' => $this->item->id,
                'quantity_delivered' => 80,
            ],
        ], $this->responsable);

        expect($bl)->toBeInstanceOf(CustomerDeliveryNote::class)
            ->and($bl->status)->toBe(DeliveryStatus::PREPARATION)
            ->and($bl->responsable_id)->toBe($this->responsable->id)
            ->and($bl->items)->toHaveCount(1)
            ->and($bl->items->first()->quantity_delivered)->toEqual(80); // Remplacé toBe par toEqual
    });

    test('met à jour le statut de la commande en livraison partielle', function () {
        $this->orderService->createDeliveryNote($this->order, [
            [
                'item_id' => $this->item->id,
                'quantity_delivered' => 50,
            ],
        ], $this->responsable);

        expect($this->order->fresh()->status)->toBe(OrderStatus::PARTIALLY_DELIVERED);
    });

    test('génère une référence unique pour chaque BL', function () {
        $bl1 = $this->orderService->createDeliveryNote($this->order, [
            ['item_id' => $this->item->id, 'quantity_delivered' => 25],
        ], $this->responsable);

        $bl2 = $this->orderService->createDeliveryNote($this->order, [
            ['item_id' => $this->item->id, 'quantity_delivered' => 25],
        ], $this->responsable);

        expect($bl1->reference)->not->toBe($bl2->reference);
    });
});

describe('CustomerOrderService - Factures', function () {

    test('crée une facture simple avec toutes les lignes de la commande', function () {
        $invoice = $this->orderService->createInvoice($this->order, InvoiceType::SIMPLE, $this->responsable);

        expect($invoice)->toBeInstanceOf(CustomerInvoice::class)
            ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
            ->and($invoice->type)->toBe(InvoiceType::SIMPLE)
            ->and($invoice->responsable_id)->toBe($this->responsable->id)
            ->and($invoice->items)->toHaveCount(1)
            ->and($invoice->total_ht)->toEqual($this->order->total_ht)
            ->and($invoice->total_ttc)->toEqual($this->order->total_ttc);
    });

    test('crée une facture d\'acompte avec le montant spécifié', function () {
        $acompteAmount = 2000.00;
        $invoice = $this->orderService->createInvoice(
            $this->order,
            InvoiceType::ACOMPTE,
            $this->responsable,
            null,
            $acompteAmount
        );

        expect($invoice->type)->toBe(InvoiceType::ACOMPTE)
            ->and($invoice->total_ht)->toEqual($acompteAmount)
            ->and($invoice->items)->toHaveCount(1)
            ->and($invoice->items->first()->name)->toContain('acompte');
    });

    test('refuse de créer une facture d\'acompte sans montant', function () {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Le montant de l\'acompte est requis.');

        $this->orderService->createInvoice($this->order, InvoiceType::ACOMPTE, $this->responsable);
    });

    test('calcule automatiquement la TVA sur les factures', function () {
        $invoice = $this->orderService->createInvoice($this->order, InvoiceType::SIMPLE, $this->responsable);

        // Supposant TVA 20% par défaut
        $expectedTTC = $invoice->total_ht * 1.20;
        expect($invoice->total_ttc)->toEqual(round($expectedTTC, 2));
    });
});

describe('CustomerOrderService - Avoirs', function () {

    test('crée un avoir client pour une facture validée', function () {
        // Créer et valider une facture d'abord
        $invoice = $this->orderService->createInvoice($this->order, InvoiceType::SIMPLE, $this->responsable);
        $invoice->update(['status' => InvoiceStatus::VALIDATED]);

        // Créer un avoir
        $creditNote = $this->orderService->createCreditNote(
            $invoice,
            500.00,
            'Produit non conforme',
            $this->responsable
        );

        expect($creditNote)->toBeInstanceOf(CustomerCreditNote::class)
            ->and($creditNote->status)->toBe(InvoiceStatus::VALIDATED)
            ->and($creditNote->responsable_id)->toBe($this->responsable->id)
            ->and($creditNote->total_ht)->toEqual(500.00);
    });

    test('refuse de créer un avoir pour une facture non validée', function () {
        $invoice = $this->orderService->createInvoice($this->order, InvoiceType::SIMPLE, $this->responsable);
        // Statut = DRAFT (non validée)

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Seules les factures validées ou payées peuvent faire l\'objet d\'un avoir.');

        $this->orderService->createCreditNote($invoice, 100.00, 'Test', $this->responsable);
    });

    test('génère une référence d\'avoir basée sur la facture', function () {
        $invoice = $this->orderService->createInvoice($this->order, InvoiceType::SIMPLE, $this->responsable);
        $invoice->update(['status' => InvoiceStatus::VALIDATED]);

        $creditNote = $this->orderService->createCreditNote($invoice, 250.00, 'Test', $this->responsable);

        expect($creditNote->reference)->toContain('AV-CL');
    });
});

describe('CustomerOrderService - Calculs financiers', function () {

    test('agrège les montants de plusieurs lignes de commande', function () {
        // Ajouter une seconde ligne
        $item2 = Item::factory()->create();
        $this->order->items()->create([
            'item_id' => $item2->id,
            'name' => 'Composant B',
            'quantity' => 50,
            'selling_price' => 100.00,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $invoice = $this->orderService->createInvoice($this->order, InvoiceType::SIMPLE, $this->responsable);

        // Total HT : (100 * 50) + (50 * 100) = 5000 + 5000 = 10000
        expect($invoice->total_ht)->toEqual(10000.00);
    });
});
