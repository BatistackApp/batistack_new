<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\SituationService;

beforeEach(function () {
    Company::factory()->create();
    $this->situationService = app(SituationService::class);

    // Données de base
    $this->customer = ThirdParty::factory()->state(['type' => 'client'])->create();
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->vatRate = VatRate::factory()->create(['is_default' => true, 'rate' => 20.00]);
    $this->responsable = User::factory()->create();

    // Création d'une commande avec lignes
    $this->order = CustomerOrder::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->responsable->id,
        'status' => OrderStatus::CONFIRMED,
        'total_ht' => 100000.00,
        'total_ttc' => 120000.00,
    ]);

    // Création de 2 lignes (Fondations et Structure)
    $this->order->items()->createMany([
        [
            'item_id' => Item::factory()->create(['reference' => 'ITEM1'])->id,
            'name' => 'Fondations',
            'quantity' => 1,
            'selling_price' => 40000.00,
            'vat_rate_id' => $this->vatRate->id,
        ],
        [
            'item_id' => Item::factory()->create(['reference' => 'ITEM2'])->id,
            'name' => 'Structure Métallique',
            'quantity' => 1,
            'selling_price' => 60000.00,
            'vat_rate_id' => $this->vatRate->id,
        ],
    ]);
});

describe('SituationService - Génération de situations', function () {

    test('génère une première situation avec avancement partiel', function () {
        $situation = $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 100, // Fondations 100%
                $this->order->items[1]->id => 10,  // Structure 10%
            ],
            startPeriod: now()->subDays(30),
            endPeriod: now()->subDays(20),
            retenueGarantieRate: 5.0,
            prorataRate: 0.0,
        );

        expect($situation)->toBeInstanceOf(CustomerSituation::class)
            ->and($situation->number)->toBe(1)
            ->and($situation->status)->toBe(InvoiceStatus::DRAFT);
    });

    test('calcule correctement les montants HT avec retenue de garantie', function () {
        // Avancement : 50% global
        // Montant HT ce mois : 50000 €
        // Retenue 5% : 2500 €
        // Net : 47500 €

        $situation = $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 50,
                $this->order->items[1]->id => 50,
            ],
            startPeriod: now()->subDays(30),
            endPeriod: now()->subDays(20),
            retenueGarantieRate: 5.0,
            prorataRate: 0.0
        );

        $montantHtBrut = 100000.00 * 0.50; // 50000
        $retenueCalculee = $montantHtBrut * 0.05; // 2500
        $montantNetAttendu = $montantHtBrut - $retenueCalculee; // 47500

        expect($situation->retenue_garantie_amount)->toEqual(2500.00)
            ->and($situation->total_ht)->toEqual(47500.00);
    });

    test('refuse que l\'avancement recule entre deux situations', function () {
        // Situation 1 : 50% d'avancement
        $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 50,
                $this->order->items[1]->id => 50,
            ],
            startPeriod: now()->subDays(30),
            endPeriod: now()->subDays(20),
        );

        // Situation 2 : on essaie de passer à 40% (recul)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ne peut pas reculer');

        $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 40,
                $this->order->items[1]->id => 40,
            ],
            startPeriod: now()->subDays(30),
            endPeriod: now()->subDays(20),
        );
    });

    test('incrémente le numéro de situation à chaque création', function () {
        $sit1 = $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 30,
                $this->order->items[1]->id => 30,
            ],
            startPeriod: now()->subDays(30),
            endPeriod: now()->subDays(20),
        );

        $sit2 = $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 100,
                $this->order->items[1]->id => 60,
            ],
            startPeriod: now()->subDays(19),
            endPeriod: now()->subDays(9),
        );

        expect($sit1->number)->toBe(1)
            ->and($sit2->number)->toBe(2);
    });

    test('valide que les pourcentages sont entre 0 et 100', function () {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('entre 0 et 100');

        $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 150, // > 100
            ],
            startPeriod: now()->subDays(30),
            endPeriod: now()->subDays(20),
        );
    });
});

describe('SituationService - Retenue de garantie et prorata', function () {

    test('applique la retenue de garantie au montant facturé ce mois', function () {
        $situation = $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 100,
                $this->order->items[1]->id => 25,
            ],
            startPeriod: now()->subDays(30),
            endPeriod: now()->subDays(20),
            retenueGarantieRate: 5.0,
            prorataRate: 0.0
        );

        // Montant brut : (40000 * 100% + 60000 * 25%) = 40000 + 15000 = 55000
        // Retenue 5% = 2750
        expect($situation->retenue_garantie_amount)->toEqual(2750.00)
            ->and($situation->total_ht)->toEqual(55000.00 - 2750.00);
    });

    test('applique le compte prorata en sus de la retenue', function () {
        $situation = $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 50,
                $this->order->items[1]->id => 50,
            ],
            startPeriod: now()->subDays(30),
            endPeriod: now()->subDays(20),
            retenueGarantieRate: 5.0,
            prorataRate: 1.0
        );

        // Montant brut : 50000
        // Retenue 5% = 2500
        // Prorata 1% = 500
        // Net = 50000 - 2500 - 500 = 47000

        expect($situation->retenue_garantie_amount)->toEqual(2500.00)
            ->and($situation->prorata_amount)->toEqual(500.00)
            ->and($situation->total_ht)->toEqual(47000.00);
    });
});

describe('SituationService - Calcul du delta facturé', function () {

    test('ne facture que le delta depuis la dernière situation', function () {
        // Situation 1 : 40% → Montant = 40000
        $sit1 = $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 100,
                $this->order->items[1]->id => 0,
            ],
            startPeriod: now()->subDays(30),
            endPeriod: now()->subDays(20),
            retenueGarantieRate: 5.0
        );

        // Situation 2 : 80% → Montant supplémentaire à facturer
        // Avancement global 80% = 80000 HT
        // Déjà facturé = 40000
        // À facturer ce mois = 40000
        $sit2 = $this->situationService->generateNextSituation(
            $this->order,
            $this->responsable,
            progressData: [
                $this->order->items[0]->id => 100,
                $this->order->items[1]->id => 50,
            ],
            startPeriod: now()->subDays(19),
            endPeriod: now()->subDays(9),
            retenueGarantieRate: 5.0
        );

        // Le montant de sit2 doit être inférieur car c'est du delta
        expect($sit2->total_ht)->toBeLessThan($sit1->total_ht);
    });
});
