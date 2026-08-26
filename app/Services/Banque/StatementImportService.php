<?php

namespace App\Services\Banque;

use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class StatementImportService
{
    /**
     * Imports a simple CSV bank statement.
     */
    public function importCsv(BankAccount $account, string $filePath): int
    {
        $imported = 0;

        if (($handle = fopen($filePath, 'r')) !== false) {
            fgetcsv($handle, 1000, ','); // Assuming comma separated header

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                // Expected format: date, description, amount
                if (count($data) >= 3) {
                    $date = Carbon::parse($data[0])->format('Y-m-d');
                    $description = $data[1];
                    $amount = (float) $data[2];
                    $hashId = 'csv_'.md5($date.$description.$amount);

                    try {
                        $tx = new BankTransaction([
                            'bank_account_id' => $account->id,
                            'date' => $date,
                            'description' => $description,
                            'amount' => $amount,
                            'type' => $amount >= 0 ? TransactionType::CREDIT : TransactionType::DEBIT,
                            'status' => TransactionStatus::PENDING,
                        ]);
                        $tx->forceFill(['external_id' => $hashId])->save();
                        $imported++;
                    } catch (QueryException $e) {
                        Log::emergency($e->getMessage());
                        // 23000 is the SQLSTATE code for integrity constraint violation (e.g. duplicate key)
                        if ($e->getCode() !== '23000') {
                            throw $e;
                        }
                    }
                }
            }
            fclose($handle);
        }

        return $imported;
    }
}
