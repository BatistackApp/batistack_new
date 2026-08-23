<?php

use App\Models\Banque\TransactionCategory;
use App\Services\Accounting\AccountingPlanService;

beforeEach(function () {
    $this->service = new AccountingPlanService();
});

test('AccountingPlanService getChargeAccount returns default when no category', function () {
    expect($this->service->getChargeAccount())->toBe('627000');
});

test('AccountingPlanService getChargeAccount returns mapped account for known category', function () {
    $category = TransactionCategory::factory()->create(['name' => 'Loyer']);

    expect($this->service->getChargeAccount($category->name))->toBe('613600');
});

test('AccountingPlanService getBankAccount returns 512100', function () {
    expect($this->service->getBankAccount())->toBe('512100');
});

test('AccountingPlanService getSupplierAccount returns 401100', function () {
    expect($this->service->getSupplierAccount())->toBe('401100');
});

test('AccountingPlanService getClientAccount returns 411100', function () {
    expect($this->service->getClientAccount())->toBe('411100');
});

test('AccountingPlanService getTvaAccount returns 445660', function () {
    expect($this->service->getTvaAccount())->toBe('445660');
});

test('AccountingPlanService getAccountsForCategory returns mapped accounts', function () {
    $category = TransactionCategory::factory()->create(['name' => 'Salaires', 'type' => 'debit']);

    $accounts = $this->service->getAccountsForCategory($category);

    expect($accounts)->toBe(['641100', '512100']);
});

test('AccountingPlanService getAccountsForCategory returns default for unknown category', function () {
    $category = TransactionCategory::factory()->create(['name' => 'Category Unknown', 'type' => 'debit']);

    $accounts = $this->service->getAccountsForCategory($category);

    expect($accounts)->toBe(['401100', '512100']);
});

test('AccountingPlanService getAccountForTransactionType returns correct accounts', function () {
    expect($this->service->getAccountForTransactionType('credit'))->toBe(['512100', '411100']);
    expect($this->service->getAccountForTransactionType('debit'))->toBe(['401100', '512100']);
});

test('AccountingPlanService setCategoryAccountMapping updates mapping', function () {
    $this->service->setCategoryAccountMapping('Test Category', '999999', '888888');

    $category = TransactionCategory::factory()->create(['name' => 'Test Category']);

    $accounts = $this->service->getAccountsForCategory($category);

    expect($accounts)->toBe(['999999', '888888']);
});
