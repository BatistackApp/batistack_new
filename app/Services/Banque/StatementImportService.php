<?php

namespace App\Services\Banque;

use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use Illuminate\Support\Carbon;

class StatementImportService
{
    /**
     * Imports a simple CSV bank statement.
     */
    public function importCsv(BankAccount $account, string $filePath): int
    {
        $imported = 0;
        
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ","); // Assuming comma separated header
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Expected format: date, description, amount
                if (count($data) >= 3) {
                    $date = Carbon::parse($data[0])->format('Y-m-d');
                    $description = $data[1];
                    $amount = (float) $data[2];
                    $hashId = 'csv_' . md5($date . $description . $amount);

                    $created = BankTransaction::firstOrCreate(
                        ['external_id' => $hashId],
                        [
                            'bank_account_id' => $account->id,
                            'date' => $date,
                            'description' => $description,
                            'amount' => $amount,
                            'type' => $amount >= 0 ? TransactionType::CREDIT : TransactionType::DEBIT,
                            'status' => TransactionStatus::PENDING,
                        ]
                    );

                    if ($created->wasRecentlyCreated) {
                        $imported++;
                    }
                }
            }
            fclose($handle);
        }
        
        return $imported;
    }
}
