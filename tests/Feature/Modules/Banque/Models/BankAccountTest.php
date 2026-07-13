<?php

use App\Enums\Banque\BankAccountType;
use App\Models\Banque\BankAccount;
use App\Models\Core\Company;

it('belongs to a company', function () {
    $company = Company::factory()->create();
    $account = BankAccount::factory()->create(['company_id' => $company->id]);

    expect($account->company->id)->toBe($company->id);
});

it('casts type correctly', function () {
    $account = BankAccount::factory()->create(['type' => BankAccountType::CHECKING]);
    expect($account->type)->toBe(BankAccountType::CHECKING);
});
