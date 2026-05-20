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
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\QuoteService;

beforeEach(function () {
    $this->quoteService = app(QuoteService::class);

    // Création d'un client test
    $this->customer = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);

    // Création d'un chantier associé
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->responsable = User::factory()->create();
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
        $order = $this->quoteService->acceptQuote($quote);

        // Vérifications
        expect($order)->toBeInstanceOf(CustomerOrder::class)
            ->and($order->status)->toBe(OrderStatus::CONFIRMED)
            ->and($order->total_ht)->toBe(10000.00)
            ->and($order->total_ttc)->toBe(12000.00)
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

        $order = $this->quoteService->acceptQuote($quote);

        // Le chantier doit avoir été créé
        expect($quote->fresh()->chantier_id)->not->toBeNull()
            ->and($quote->fresh()->chantier->status)->toBe(ChantierStatus::PLANNED)
            ->and($quote->fresh()->chantier->budget_total_ht)->toBe(5000.00);
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
                'quantity' => 100,
                'selling_price' => 50.00,
                'vat_rate_id' => VatRate::where('is_default', true)->first()->id,
            ],
            [
                'item_id' => Item::factory()->create()->id,
                'name' => 'Main d\'œuvre',
                'quantity' => 40,
                'selling_price' => 75.00,
                'vat_rate_id' => VatRate::where('is_default', true)->first()->id,
            ],
        ]);

        $order = $this->quoteService->acceptQuote($quote);

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
        $this->expectExceptionMessage('Ce devis ne peut pas être accepté');

        $this->quoteService->acceptQuote($quote);
    });

    test('utilise la date d\'adresse du client comme adresse du chantier par défaut', function () {
        // Créer une adresse pour le client
        $address = $this->customer->addresses()->create([
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

        $order = $this->quoteService->acceptQuote($quote);

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

        $order = $this->quoteService->acceptQuote($quote);

        expect($order->total_ht)->toBe(8500.50)
            ->and($order->total_ttc)->toBe(10200.60);
    });
});
