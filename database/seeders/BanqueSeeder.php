<?php

namespace Database\Seeders;

use App\Enums\Banque\BankAccountType;
use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use App\Models\Banque\TransactionCategory;
use App\Models\Core\Company;
use Illuminate\Database\Seeder;

class BanqueSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        // Comptes bancaires
        $mainAccount = BankAccount::create([
            'company_id' => $company->id,
            'name' => 'Compte Principal BP',
            'type' => BankAccountType::CHECKING,
            'iban' => 'FR7630006000011234567890189',
            'bic' => 'BNPAFRPPXXX',
            'currency' => 'EUR',
            'balance' => 45250.00,
            'bridge_account_id' => null,
        ]);

        $savingsAccount = BankAccount::create([
            'company_id' => $company->id,
            'name' => 'Compte Épargne Pro',
            'type' => BankAccountType::SAVINGS,
            'iban' => 'FR7630006000019876543210123',
            'bic' => 'BNPAFRPPXXX',
            'currency' => 'EUR',
            'balance' => 12800.00,
            'bridge_account_id' => null,
        ]);

        // Catégories de transactions
        $categories = [
            TransactionCategory::create(['name' => 'Salaires', 'color' => '#EF4444', 'type' => 'debit']),
            TransactionCategory::create(['name' => 'Fournisseurs', 'color' => '#F97316', 'type' => 'debit']),
            TransactionCategory::create(['name' => 'Loyer', 'color' => '#EAB308', 'type' => 'debit']),
            TransactionCategory::create(['name' => 'Assurances', 'color' => '#84CC16', 'type' => 'debit']),
            TransactionCategory::create(['name' => 'Carburant', 'color' => '#22C55E', 'type' => 'debit']),
            TransactionCategory::create(['name' => 'Clients', 'color' => '#3B82F6', 'type' => 'credit']),
            TransactionCategory::create(['name' => 'Subventions', 'color' => '#8B5CF6', 'type' => 'credit']),
        ];

        // Transactions historiques (6 derniers mois)
        $descriptions = [
            'Virement salaire Janvier', 'Paiement fournisseur Tuiles Pro',
            'Facture client Résidence Martin', 'Prélèvement loyer agence',
            'Assurance flotte vehicle', 'Carburant véhicule V-001',
            'Virement client BL-2026-001', 'Paiement sous-traitant Charpente',
            'Remboursement frais déplacement', 'Abonnement logiciel comptable',
        ];

        for ($i = 0; $i < 40; $i++) {
            $type = collect(TransactionType::cases())->random();
            $amount = $type === TransactionType::CREDIT
                ? rand(500, 15000) / 100
                : -(rand(100, 8000) / 100);

            BankTransaction::create([
                'bank_account_id' => $mainAccount->id,
                'external_id' => uniqid('TX-'),
                'date' => now()->subDays(rand(0, 180))->format('Y-m-d'),
                'description' => $descriptions[array_rand($descriptions)],
                'amount' => $amount,
                'type' => $type->value,
                'status' => collect(TransactionStatus::cases())->random()->value,
                'transaction_category_id' => $categories[array_rand($categories)]->id,
            ]);
        }
    }
}
