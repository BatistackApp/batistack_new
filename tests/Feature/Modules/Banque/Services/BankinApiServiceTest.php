<?php

use App\Models\Banque\BankAccount;
use App\Models\Core\Company;
use App\Services\Banque\BankinApiService;

it('simulates transaction sync correctly', function () {
    $company = Company::factory()->create();
    $account = BankAccount::factory()->create(['company_id' => $company->id]);
    
    $service = new BankinApiService();
    $imported = $service->syncTransactions($account);

    expect($imported)->toBeGreaterThanOrEqual(1)
        ->and($imported)->toBeLessThanOrEqual(5)
        ->and($account->transactions()->count())->toBe($imported);
});
