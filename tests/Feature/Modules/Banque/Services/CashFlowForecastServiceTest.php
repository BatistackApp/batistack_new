<?php

use App\Models\Banque\BankAccount;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Tiers\ThirdParty;
use App\Services\Banque\CashFlowForecastService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates cash flow forecast correctly over 30 days', function () {
    // Current balance: 1000
    BankAccount::factory()->create(['balance' => 1000]);

    $customer = ThirdParty::factory()->create();
    $supplier = ThirdParty::factory()->create();

    // Income in 5 days: +500
    $inv1 = CustomerInvoice::factory()->create([
        'client_id' => $customer->id,
        'status' => 'draft',
        'due_date' => Carbon::today()->addDays(5),
        'total_ttc' => 500.0,
    ]);

    // Expense in 10 days: -200
    $inv2 = SupplierInvoice::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => 'draft',
        'due_date' => Carbon::today()->addDays(10),
        'amount_ttc' => 200.0,
    ]);

    // Paid invoice (should be ignored)
    $inv3 = CustomerInvoice::factory()->create([
        'client_id' => $customer->id,
        'status' => 'paid',
        'due_date' => Carbon::today()->addDays(2),
        'total_ttc' => 999.0,
    ]);

    $service = new CashFlowForecastService();
    $forecast = $service->getForecast(30);

    expect(count($forecast['labels']))->toBe(31); // Day 0 to 30 = 31 points
    
    // Day 0 (Today)
    expect($forecast['balances_confirmed'][0])->toBe(1000.0);
    expect($forecast['incomes'][0])->toBe(0.0);
    
    // Day 5 (+500)
    expect($forecast['balances_confirmed'][5])->toBe(1500.0);
    expect($forecast['incomes'][5])->toBe(500.0);

    // Day 10 (-200) -> balance becomes 1500 - 200 = 1300
    expect($forecast['balances_confirmed'][10])->toBe(1300.0);
    expect($forecast['expenses'][10])->toBe(200.0);

    // Day 30 (end)
    expect($forecast['balances_confirmed'][30])->toBe(1300.0);
});
