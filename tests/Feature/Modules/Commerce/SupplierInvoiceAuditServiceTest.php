<?php

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\ReceiptNote;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\SupplierInvoiceAuditService;

beforeEach(function () {
    $this->auditService = app(SupplierInvoiceAuditService::class);

    // Données de base
    $this->supplier = ThirdParty::factory()->state(['type' => 'supplier'])->create();
    $this->chantier = Chantier::factory()->create();
    $this->vatRate = VatRate::factory()->create(['is_default' => true, 'rate' => 20.00]);

    // Créer un BC (Bon de Commande) fournisseur
    $this->purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $this->supplier->id,
        'chantier_id' => $this->chantier->id,
        'status' => OrderStatus::CONFIRMED,
        'total_ht' => 10000.00,
    ]);

    // Créer 2 lignes du BC
    $item1 = Item::factory()->create();
    $item2 = Item::factory()->create();

    $this->purchaseOrder->items()->createMany([
        [
            'item_id' => $item1->id,
            'name' => 'Acier brut',
            'quantity' => 100,
            'price_unit_ht' => 50.00,
            'vat_rate_id' => $this->vatRate->id,
        ],
        [
            'item_id' => $item2->id,
            'name' => 'Composant B',
            'quantity' => 50,
            'price_unit_ht' => 100.00,
            'vat_rate_id' => $this->vatRate->id,
        ],
    ]);

    // Créer un BR (Bon de Réception) pour chaque ligne
    $br = ReceiptNote::create([
        'purchase_order_id' => $this->purchaseOrder->id,
        'reference' => 'BR-TEST-001',
        'status' => DeliveryStatus::DELIVERED,
        'received_at' => now(),
    ]);

    $br->items()->createMany([
        [
            'purchase_order_item_id' => $this->purchaseOrder->items[0]->id,
            'quantity_received' => 100, // Confirmé : 100
        ],
        [
            'purchase_order_item_id' => $this->purchaseOrder->items[1]->id,
            'quantity_received' => 50, // Confirmé : 50
        ],
    ]);
});

describe('SupplierInvoiceAuditService - Contrôle 3 voies', function () {

    test('valide une facture conforme aux BC et BR', function () {
        // Facture conforme : mêmes quantités et prix
        $invoice = SupplierInvoice::create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'reference' => 'FACT-TEST-001',
            'amount_ht' => 10000.00,
            'amount_ttc' => 12000.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->items()->createMany([
            [
                'item_id' => $this->purchaseOrder->items[0]->item_id,
                'name' => 'Acier brut',
                'quantity' => 100,
                'price_unit' => 50.00,
                'vat_rate_id' => $this->vatRate->id,
            ],
            [
                'item_id' => $this->purchaseOrder->items[1]->item_id,
                'name' => 'Composant B',
                'quantity' => 50,
                'price_unit' => 100.00,
                'vat_rate_id' => $this->vatRate->id,
            ],
        ]);

        $result = $this->auditService->auditInvoice($invoice);

        expect($result['is_valid'])->toBeTrue()
            ->and($result['disputes'])->toBeEmpty();
    });

    test('détecte une surfacturation en quantité', function () {
        // Facture avec quantité supérieure aux BR
        $invoice = SupplierInvoice::create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'reference' => 'FACT-SURF-QTE',
            'amount_ht' => 10000.00,
            'amount_ttc' => 12000.00, // Ajout du montant TTC
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->items()->create([
            'item_id' => $this->purchaseOrder->items[0]->item_id,
            'name' => 'Acier brut',
            'quantity' => 120, // 120 facturé vs 100 reçu
            'price_unit' => 50.00,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $result = $this->auditService->auditInvoice($invoice);

        expect($result['is_valid'])->toBeFalse()
            ->and($result['disputes'])->not->toBeEmpty()
            ->and(implode(' ', $result['disputes']))->toContain('SURFACTURATION QTÉ');
    });

    test('détecte une surfacturation en prix', function () {
        // Facture avec prix supérieur au BC + 2%
        $priceBC = 50.00;
        $prixMaxAutorise = $priceBC * 1.02; // 51.00

        $invoice = SupplierInvoice::create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'reference' => 'FACT-SURF-PRIX',
            'amount_ht' => 10000.00,
            'amount_ttc' => 12000.00, // Ajout du montant TTC
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->items()->create([
            'item_id' => $this->purchaseOrder->items[0]->item_id,
            'name' => 'Acier brut',
            'quantity' => 100,
            'price_unit' => 52.00, // Dépassement du seuil
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $result = $this->auditService->auditInvoice($invoice);

        expect($result['is_valid'])->toBeFalse()
            ->and(implode(' ', $result['disputes']))->toContain('SURFACTURATION PRIX');
    });

    test('accepte une petite variation de prix (jusqu\'à 2%)', function () {
        // Facture avec prix = BC + 2% (tolérance max)
        $invoice = SupplierInvoice::create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'reference' => 'FACT-TOLERANCE',
            'amount_ht' => 10000.00,
            'amount_ttc' => 12000.00, // Ajout du montant TTC
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->items()->create([
            'item_id' => $this->purchaseOrder->items[0]->item_id,
            'name' => 'Acier brut',
            'quantity' => 100,
            'price_unit' => 51.00, // 50 * 1.02 = 51.00
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $result = $this->auditService->auditInvoice($invoice);

        expect($result['is_valid'])->toBeTrue();
    });

    test('passage en LITIGE automatique si audit échoue', function () {
        $invoice = SupplierInvoice::create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'reference' => 'FACT-LITIGE',
            'amount_ht' => 10000.00,
            'amount_ttc' => 12000.00, // Ajout du montant TTC
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->items()->create([
            'item_id' => $this->purchaseOrder->items[0]->item_id,
            'name' => 'Acier brut',
            'quantity' => 150, // Surfacturation
            'price_unit' => 50.00,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $result = $this->auditService->auditInvoice($invoice);

        // La facture doit passer en LITIGE
        expect($invoice->fresh()->status)->toBe(InvoiceStatus::LITIGE)
            ->and($invoice->fresh()->dispute_reason)->not->toBeEmpty();
    });

    test('détecte un article facturé non présent sur le BC', function () {
        $itemNonCommande = Item::factory()->create();

        $invoice = SupplierInvoice::create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'reference' => 'FACT-ITEM-EXTRA',
            'amount_ht' => 10000.00,
            'amount_ttc' => 12000.00, // Ajout du montant TTC
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice->items()->create([
            'item_id' => $itemNonCommande->id,
            'name' => 'Article mystère',
            'quantity' => 10,
            'price_unit' => 100.00,
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $result = $this->auditService->auditInvoice($invoice);

        expect($result['is_valid'])->toBeFalse()
            ->and(implode(' ', $result['disputes']))->toContain('ne figure pas');
    });
});

describe('SupplierInvoiceAuditService - Cas limites', function () {

    test('gère les factures sans BC rattaché', function () {
        $invoiceOrpheline = SupplierInvoice::create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => null, // Pas de BC
            'reference' => 'FACT-ORPHELINE',
            'amount_ht' => 5000.00,
            'amount_ttc' => 6000.00, // Ajout du montant TTC
            'status' => InvoiceStatus::DRAFT,
        ]);

        $result = $this->auditService->auditInvoice($invoiceOrpheline);

        expect($result['is_valid'])->toBeFalse()
            ->and(implode(' ', $result['disputes']))->toContain('n\'est rattachée');
    });

    test('traite plusieurs erreurs simultanées', function () {
        $invoice = SupplierInvoice::create([
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $this->purchaseOrder->id,
            'reference' => 'FACT-MULTI-ERREURS',
            'amount_ht' => 10000.00,
            'amount_ttc' => 12000.00, // Ajout du montant TTC
            'status' => InvoiceStatus::DRAFT,
        ]);

        // 2 erreurs : surqté + surprix
        $invoice->items()->create([
            'item_id' => $this->purchaseOrder->items[0]->item_id,
            'name' => 'Acier brut',
            'quantity' => 120, // Surqté
            'price_unit' => 52.00, // Surprix
            'vat_rate_id' => $this->vatRate->id,
        ]);

        $result = $this->auditService->auditInvoice($invoice);

        expect($result['disputes'])->toHaveCount(2);
    });
});
