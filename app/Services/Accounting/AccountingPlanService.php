<?php

namespace App\Services\Accounting;

use App\Models\Banque\TransactionCategory;

class AccountingPlanService
{
    private array $categoryAccountMap = [
        // Category name => [debit_account, credit_account]
        'Salaires' => ['641100', '512100'],
        'Charges sociales' => ['645100', '431000'],
        'Fournisseurs' => ['401100', '512100'],
        'Clients' => ['512100', '411100'],
        'Loyer' => ['613600', '512100'],
        'Énergie' => ['605200', '512100'],
        'Assurance' => ['613700', '512100'],
        'Télécom' => ['624000', '512100'],
        'Frais bancaires' => ['625000', '512100'],
        'Impôts' => ['631100', '512100'],
        'TVA' => ['445660', '512100'],
        'Autres charges' => ['627000', '512100'],
        'Autres produits' => ['512100', '707000'],
    ];

    private array $defaultAccounts = [
        'credit' => ['512100', '411100'], // Banque / Clients
        'debit' => ['401100', '512100'], // Fournisseurs / Banque
    ];

    public function getAccountsForCategory(TransactionCategory $category): array
    {
        $name = $category->name;

        if (isset($this->categoryAccountMap[$name])) {
            return $this->categoryAccountMap[$name];
        }

        // Try partial match
        foreach ($this->categoryAccountMap as $mapName => $accounts) {
            if (str_contains($name, $mapName) || str_contains($mapName, $name)) {
                return $accounts;
            }
        }

        return $this->defaultAccounts[$category->type ?? 'debit'] ?? $this->defaultAccounts['debit'];
    }

    public function getAccountForTransactionType(string $type): array
    {
        return $this->defaultAccounts[$type] ?? $this->defaultAccounts['debit'];
    }

    public function getChargeAccount(?string $categoryName = null): string
    {
        if ($categoryName && isset($this->categoryAccountMap[$categoryName])) {
            return $this->categoryAccountMap[$categoryName][0];
        }

        return '627000'; // Autres services extérieurs
    }

    public function getBankAccount(): string
    {
        return '512100';
    }

    public function getSupplierAccount(): string
    {
        return '401100';
    }

    public function getClientAccount(): string
    {
        return '411100';
    }

    public function getTvaAccount(): string
    {
        return '445660';
    }

    public function setCategoryAccountMapping(string $categoryName, string $debitAccount, string $creditAccount): void
    {
        $this->categoryAccountMap[$categoryName] = [$debitAccount, $creditAccount];
    }
}
