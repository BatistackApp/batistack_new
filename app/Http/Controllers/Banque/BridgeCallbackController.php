<?php

namespace App\Http\Controllers\Banque;

use App\Http\Controllers\Controller;
use App\Models\Banque\BankAccount;
use App\Models\Core\Company;
use App\Services\Banque\BridgeApiService;
use Illuminate\Http\Request;

class BridgeCallbackController extends Controller
{
    /**
     * Handle the callback from Bridge after the user completes the Connect flow.
     */
    public function __invoke(Request $request, BridgeApiService $bridgeService)
    {
        // Bridge will redirect here. Let's sync transactions for all banks linked to the company
        // For security, the external user ID is tied to the company. Let's assume we can get the company 
        // from the current logged-in user or session in a real scenario.
        
        $user = $request->user();
        if (!$user || !$user->company_id) {
            return redirect('/')->with('error', 'Authentication required.');
        }

        $company = Company::find($user->company_id);
        
        // Let's trigger a sync for all bank accounts of this company
        $accounts = BankAccount::where('company_id', $company->id)->whereNotNull('bridge_account_id')->get();
        $importedTotal = 0;

        foreach ($accounts as $account) {
            try {
                $importedTotal += $bridgeService->syncTransactions($account);
            } catch (\Exception $e) {
                // Log and continue
                \Illuminate\Support\Facades\Log::error('Bridge sync failed for account ' . $account->id . ': ' . $e->getMessage());
            }
        }

        return redirect('/banque/bank-accounts')->with('success', "Connexion terminée. {$importedTotal} transactions importées.");
    }
}
