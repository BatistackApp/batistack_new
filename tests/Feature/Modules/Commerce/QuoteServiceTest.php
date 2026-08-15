<?php

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Enums\Tiers\AddressType;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\QuoteService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Company::factory()->create();
    $this->quoteService = app(QuoteService::class);

    // Création d'un client test
    $this->customer = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);

    // Création d'un chantier associé
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->responsable = User::factory()->create();

    // Création d'un taux de TVA par défaut
    $this->defaultVatRate = VatRate::factory()->create(['is_default' => true, 'rate' => 20]);

    Notification::fake();
    Queue::fake();
});

describe('QuoteService - Acceptation de devis', function () {

    test('accepte un devis et crée une commande client automatiquement', function () {
        // Création d'un devis en statut SENT
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'status' => QuoteStatus::SENT,
            'total_ht' => 10000.00,
            'total_ttc' => 12000.00,
            'responsable_id' => $this->responsable->id,
        ]);

        // Acceptation du devis
        $order = $this->quoteService->acceptQuote($quote, $this->responsable);

        // Vérifications
        expect($order)->toBeInstanceOf(CustomerOrder::class)
            ->and($order->status)->toBe(OrderStatus::CONFIRMED)
            ->and($order->responsable_id)->toBe($this->responsable->id)
            ->and($order->total_ht)->toEqual(10000.00) // Utiliser toEqual pour la comparaison de nombres
            ->and($order->total_ttc)->toEqual(12000.00) // Utiliser toEqual pour la comparaison de nombres
            ->and($quote->fresh()->status)->toBe(QuoteStatus::SIGNED)
            ->and($quote->fresh()->signed_at)->not->toBeNull();
    });

    test('crée un chantier automatiquement si le devis n\'en a pas', function () {
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => null, // Pas de chantier
            'status' => QuoteStatus::DRAFT,
            'total_ht' => 5000.00,
            'responsable_id' => $this->responsable->id,
        ]);

        $order = $this->quoteService->acceptQuote($quote, $this->responsable);

        // Le chantier doit avoir été créé
        expect($quote->fresh()->chantier_id)->not->toBeNull()
            ->and($quote->fresh()->chantier->status)->toBe(ChantierStatus::PLANNED)
            ->and($quote->fresh()->chantier->budget_total_ht)->toEqual(5000.00); // Utiliser toEqual
    });

    test('duplique les lignes du devis dans la commande de manière immuable', function () {
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'status' => QuoteStatus::SENT,
            'responsable_id' => $this->responsable->id,
        ]);

        // Création de 2 lignes de devis
        $quote->items()->createMany([
            [
                'item_id' => Item::factory()->create()->id,
                'name' => 'Matériau A',
                'purchase_price' => 25.00, // Ajout du prix d'achat manquant
                'quantity' => 100,
                'selling_price' => 50.00,
                'vat_rate_id' => $this->defaultVatRate->id,
            ],
            [
                'item_id' => Item::factory()->create()->id,
                'name' => 'Main d\'œuvre',
                'purchase_price' => 30.00, // Ajout du prix d'achat manquant
                'quantity' => 40,
                'selling_price' => 75.00,
                'vat_rate_id' => $this->defaultVatRate->id,
            ],
        ]);

        $order = $this->quoteService->acceptQuote($quote, $this->responsable);
        $order->refresh();

        // Les lignes doivent être copiées dans la commande
        expect($order->items)->toHaveCount(2)
            ->and($order->items->pluck('name'))->toContain('Matériau A', 'Main d\'œuvre');
    });

    test('refuse d\'accepter un devis qui n\'est pas en statut SENT ou DRAFT', function () {
        $quote = CustomerQuote::factory()->create([
            'status' => QuoteStatus::REJECTED, // Statut non autorisé
            'responsable_id' => $this->responsable->id,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ce devis ne peut pas être accepté dans son état actuel.');

        $this->quoteService->acceptQuote($quote, $this->responsable);
    });

    test('utilise la date d\'adresse du client comme adresse du chantier par défaut', function () {
        // Créer une adresse pour le client
        $this->customer->addresses()->create([
            'street' => '123 Rue de la Paix',
            'zip_code' => '75000',
            'city' => 'Paris',
            'is_default' => true,
            'type' => AddressType::SITE,
        ]);

        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => null,
            'status' => QuoteStatus::DRAFT,
            'responsable_id' => $this->responsable->id,
        ]);

        $order = $this->quoteService->acceptQuote($quote, $this->responsable);

        $chantier = $quote->fresh()->chantier;
        expect($chantier->address)->toBe('123 Rue de la Paix')
            ->and($chantier->zip_code)->toBe('75000')
            ->and($chantier->city)->toBe('Paris');
    });
});

describe('QuoteService - Gestion des montants', function () {

    test('préserve les montants HT et TTC lors de la conversion en commande', function () {
        $quote = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'status' => QuoteStatus::SENT,
            'total_ht' => 8500.50,
            'total_ttc' => 10200.60,
            'responsable_id' => $this->responsable->id,
        ]);

        $order = $this->quoteService->acceptQuote($quote, $this->responsable);

        expect($order->total_ht)->toEqual(8500.50) // Utiliser toEqual
            ->and($order->total_ttc)->toEqual(10200.60); // Utiliser toEqual
    });
});

describe('QuoteService - Avenants (travaux supplémentaires)', function () {

    test('génère une référence d\'avenant AV-YYYY-NNN', function () {
        $ref = $this->quoteService->generateReferenceAvenant();

        expect($ref)->toMatch('/^AV-\d{4}-\d{3}$/');
    });

    test('enrichit la commande principale et rehausse le budget du chantier', function () {
        $this->chantier->update(['budget_total_ht' => 10000.00]);

        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'status' => OrderStatus::CONFIRMED,
            'total_ht' => 10000.00,
            'total_ttc' => 12000.00,
            'responsable_id' => $this->responsable->id,
        ]);

        $avenant = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'parent_order_id' => $order->id,
            'status' => QuoteStatus::SENT,
            'total_ht' => 2000.00,
            'total_ttc' => 2400.00,
            'responsable_id' => $this->responsable->id,
            'is_avenant' => true,
        ]);

        $avenant->items()->create([
            'item_id' => Item::factory()->create()->id,
            'name' => 'Travaux supplémentaires',
            'purchase_price' => 700.00,
            'quantity' => 2,
            'selling_price' => 1000.00,
            'vat_rate_id' => $this->defaultVatRate->id,
        ]);

        $result = $this->quoteService->acceptQuote($avenant, $this->responsable);

        expect($result->id)->toBe($order->id)
            ->and($result->refresh()->items)->toHaveCount(1)
            ->and($result->refresh()->total_ht)->toEqual(12000.00)
            ->and($this->chantier->refresh()->budget_total_ht)->toEqual(12000.00)
            ->and($avenant->fresh()->status)->toBe(QuoteStatus::SIGNED);
    });

    test('ne crée pas de nouveau chantier pour un avenant', function () {
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'status' => OrderStatus::CONFIRMED,
            'total_ht' => 5000.00,
            'total_ttc' => 6000.00,
            'responsable_id' => $this->responsable->id,
        ]);

        $before = Chantier::count();

        $avenant = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'parent_order_id' => $order->id,
            'status' => QuoteStatus::DRAFT,
            'total_ht' => 500.00,
            'total_ttc' => 600.00,
            'responsable_id' => $this->responsable->id,
            'is_avenant' => true,
        ]);

        $this->quoteService->acceptQuote($avenant, $this->responsable);

        expect(Chantier::count())->toBe($before);
    });

    test('refuse un avenant dont la commande parente est incohérente avec le devis', function () {
        $otherChantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
        $order = CustomerOrder::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $otherChantier->id,
            'status' => OrderStatus::CONFIRMED,
            'responsable_id' => $this->responsable->id,
        ]);

        $avenant = CustomerQuote::factory()->create([
            'client_id' => $this->customer->id,
            'chantier_id' => $this->chantier->id,
            'parent_order_id' => $order->id,
            'status' => QuoteStatus::SENT,
            'responsable_id' => $this->responsable->id,
            'is_avenant' => true,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La commande principale doit appartenir au même client et au même chantier que l\'avenant.');

        $this->quoteService->acceptQuote($avenant, $this->responsable);
    });
});
