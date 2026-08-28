<?php

use App\Enums\Commerce\OrderStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerOrderItem;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;

beforeEach(function () {
    Company::factory()->create();
    $this->customer = ThirdParty::factory()->create();
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->user = User::factory()->create();
    $this->quote = CustomerQuote::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
    ]);
    $this->order = CustomerOrder::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'customer_quote_id' => $this->quote->id,
        'responsable_id' => $this->user->id,
    ]);
});

test('order has correct relationships', function () {
    expect($this->order->client)->toBeInstanceOf(ThirdParty::class)
        ->and($this->order->chantier)->toBeInstanceOf(Chantier::class)
        ->and($this->order->quote)->toBeInstanceOf(CustomerQuote::class)
        ->and($this->order->user)->toBeInstanceOf(User::class);
});

test('order calculates total tva correctly', function () {
    $vatRate = VatRate::factory()->create(['rate' => 20]);

    // total_ht = 100 * 2 = 200 => TVA = 40
    CustomerOrderItem::factory()->create([
        'customer_order_id' => $this->order->id,
        'quantity' => 2,
        'selling_price' => 100,
        'total_ht' => 200,
        'vat_rate_id' => $vatRate->id,
    ]);

    // total_ht = 50 * 3 = 150 => TVA = 30
    CustomerOrderItem::factory()->create([
        'customer_order_id' => $this->order->id,
        'quantity' => 3,
        'selling_price' => 50,
        'total_ht' => 150,
        'vat_rate_id' => $vatRate->id,
    ]);

    $this->order->refresh();

    expect($this->order->total_tva)->toBeFloat()->toEqual(70.0);
});

test('order casts attributes correctly', function () {
    $this->order->update(['status' => OrderStatus::BILLED]);

    expect($this->order->status)->toBeInstanceOf(OrderStatus::class)
        ->and($this->order->status)->toBe(OrderStatus::BILLED);
});
