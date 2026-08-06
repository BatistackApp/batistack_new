<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Banque\BankAccount;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Services\Commerce\SepaExportService;
use Illuminate\Database\Eloquent\Collection;

it('generates sepa xml for supplier invoices', function () {
    // 1. Arrange
    $company = Company::factory()->create([
        'legal_name' => 'Mon Entreprise SAS'
    ]);
    
    $bankAccount = BankAccount::factory()->create([
        'company_id' => $company->id,
        'iban' => 'FR7612345678901234567890123',
        'bic' => 'TESTFRPP'
    ]);

    $supplier = ThirdParty::factory()->create([
        'name' => 'Mon Fournisseur',
        'iban' => 'FR7698765432109876543210987',
        'bic' => 'FOURNIFR',
    ]);

    $invoice = SupplierInvoice::factory()->create([
        'supplier_id' => $supplier->id,
        'reference' => 'FAC-FOURNI-001',
        'status' => InvoiceStatus::BON_A_PAYER,
        'amount_ht' => 100,
        'amount_ttc' => 120,
    ]);

    $invoices = new Collection([$invoice]);
    $service = new SepaExportService();

    // 2. Act
    $xml = $service->generateForSupplierInvoices($invoices);

    // 3. Assert
    expect($xml)->toBeString();
    expect($xml)->toContain('Mon Entreprise SAS');
    expect($xml)->toContain('FR7612345678901234567890123'); // Debtor IBAN
    expect($xml)->toContain('FR7698765432109876543210987'); // Creditor IBAN
    expect($xml)->toContain('120.00'); // 120 EUR (la lib gère les centimes vers décimales)
    expect($xml)->toContain('Reglement facture FAC-FOURNI-001'); // les accents sont retirés
});

it('throws exception if company bank account is missing', function () {
    $company = Company::factory()->create();
    BankAccount::query()->delete();

    $invoices = new Collection();
    $service = new SepaExportService();

    expect(fn () => $service->generateForSupplierInvoices($invoices))
        ->toThrow(\Exception::class, "Le compte en banque principal de l'entreprise (ou son IBAN/BIC) n'est pas configuré.");
});

it('throws exception if supplier is missing iban', function () {
    $company = Company::factory()->create();
    BankAccount::factory()->create(['company_id' => $company->id, 'iban' => 'FR123', 'bic' => 'BIC']);

    $supplier = ThirdParty::factory()->create(['name' => 'Sans Iban', 'iban' => null]);
    $invoice = SupplierInvoice::factory()->create(['supplier_id' => $supplier->id, 'amount_ttc' => 100]);

    $invoices = new Collection([$invoice]);
    $service = new SepaExportService();

    expect(fn () => $service->generateForSupplierInvoices($invoices))
        ->toThrow(\Exception::class, "Le fournisseur SANS IBAN n'a pas d'IBAN ou de BIC renseigné sur sa fiche.");
});
