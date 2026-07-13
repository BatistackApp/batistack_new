<?php

use App\Enums\Banque\TransactionType;
use App\Models\Banque\BankAccount;
use App\Models\Core\Company;
use App\Services\Banque\StatementImportService;
use Illuminate\Support\Facades\Storage;

it('imports csv statements correctly', function () {
    $company = Company::factory()->create();
    $account = BankAccount::factory()->create(['company_id' => $company->id]);
    
    // Create a dummy CSV file
    $csvContent = "Date,Libellé,Montant\n";
    $csvContent .= "2026-07-10,Virement Client Dupont,1500.00\n";
    $csvContent .= "2026-07-11,Prélèvement Fournisseur Materiaux,-500.50\n";
    
    Storage::disk('local')->put('test_statement.csv', $csvContent);
    $filePath = Storage::disk('local')->path('test_statement.csv');

    $service = new StatementImportService();
    $imported = $service->importCsv($account, $filePath);

    expect($imported)->toBe(2)
        ->and($account->transactions()->count())->toBe(2);

    $credit = $account->transactions()->where('type', TransactionType::CREDIT)->first();
    expect((float) $credit->amount)->toBe(1500.00)
        ->and($credit->description)->toBe('Virement Client Dupont');

    $debit = $account->transactions()->where('type', TransactionType::DEBIT)->first();
    expect((float) $debit->amount)->toBe(-500.50)
        ->and($debit->description)->toBe('Prélèvement Fournisseur Materiaux');

    Storage::disk('local')->delete('test_statement.csv');
});
