<?php

use App\Enums\RH\ExpenseReportStatus;
use App\Models\Banque\BankAccount;
use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Models\RH\ExpenseReport;
use App\Services\RH\SepaExportService;
use Illuminate\Database\Eloquent\Collection;

it('generates a sepa xml file successfully for valid employees and company', function () {
    $company = Company::factory()->create(['legal_name' => 'Test Company']);
    $bankAccount = BankAccount::factory()->create([
        'company_id' => $company->id,
        'iban' => 'FR7612345678901234567890123',
        'bic' => 'TESTBICX',
    ]);

    $employee = Employee::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'iban' => 'FR7698765432109876543210987',
        'bic' => 'EMPTESTX',
    ]);

    $report = ExpenseReport::factory()->create([
        'employee_id' => $employee->id,
        'total_amount' => 150.50,
        'status' => ExpenseReportStatus::VALIDATED,
    ]);

    $service = new SepaExportService;
    $xml = $service->generateForExpenseReports(new Collection([$report]));

    expect($xml)->toContain('FR7612345678901234567890123')
        ->toContain('FR7698765432109876543210987')
        ->toContain('150.50') // amount in decimal
        ->toContain('John Doe')
        ->toContain('Test Company');
});

it('throws exception if company bank account is missing iban', function () {
    $company = Company::factory()->create();
    $bankAccount = BankAccount::factory()->create([
        'company_id' => $company->id,
        'iban' => null, // missing IBAN
        'bic' => 'TESTBICX',
    ]);

    $report = ExpenseReport::factory()->create();

    $service = new SepaExportService;

    expect(fn () => $service->generateForExpenseReports(new Collection([$report])))
        ->toThrow(Exception::class, "Le compte en banque principal de l'entreprise (ou son IBAN/BIC) n'est pas configuré.");
});

it('throws exception if employee is missing iban', function () {
    $company = Company::factory()->create();
    $bankAccount = BankAccount::factory()->create([
        'company_id' => $company->id,
        'iban' => 'FR7612345678901234567890123',
        'bic' => 'TESTBICX',
    ]);

    $employee = Employee::factory()->create([
        'iban' => null, // missing IBAN
        'bic' => 'EMPTESTX',
    ]);

    $report = ExpenseReport::factory()->create([
        'employee_id' => $employee->id,
        'status' => ExpenseReportStatus::VALIDATED,
    ]);

    $service = new SepaExportService;

    expect(fn () => $service->generateForExpenseReports(new Collection([$report])))
        ->toThrow(Exception::class, "L'employé {$employee->first_name} {$employee->last_name} n'a pas d'IBAN ou de BIC renseigné sur sa fiche.");
});
