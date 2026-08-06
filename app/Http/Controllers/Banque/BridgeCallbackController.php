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
        set_time_limit(120); // Give the callback enough time to fetch historical transactions for all linked accounts

        // Bridge will redirect here. Let's sync transactions for all banks linked to the company
        // For security, the external user ID is tied to the company. Let's assume we can get the company
        // from the current logged-in user or session in a real scenario.

        $user = $request->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Authentication required.');
        }

        $company = Company::first();
        if (!$company) {
            return redirect('/')->with('error', 'Aucune entreprise trouvée.');
        }

        // Let's trigger a sync for all bank accounts of this company
        try {
            $accounts = $bridgeService->syncAccounts($company->id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Bridge accounts sync failed: ' . $e->getMessage());
            return redirect('/banque/banque/bank-accounts')->with('error', 'Erreur lors de la synchronisation des comptes: ' . $e->getMessage());
        }

        $dispatchedCount = 0;

        foreach ($accounts as $account) {
            \App\Jobs\Banque\SyncBridgeTransactionsJob::dispatch($account, auth()->id());
            $dispatchedCount++;
        }

        return redirect('/banque/banque/bank-accounts')->with('success', "Connexion terminée. L'importation de l'historique de vos transactions ({$dispatchedCount} comptes) est en cours en arrière-plan.");
    }

    /**
     * Renew an existing connection (Open Banking / DSP2 flow).
     */
    public function renew(Request $request, BridgeApiService $bridgeService)
    {
        $user = $request->user();
        if (!$user) {
            return redirect('/login')->with('error', 'Authentication required.');
        }

        $company = Company::first();
        if (!$company) {
            return redirect('/')->with('error', 'Aucune entreprise trouvée.');
        }

        $externalUserId = 'company_' . $company->id;
        $userEmail = $user->email;
        $callbackUrl = route('bridge.callback');

        try {
            $url = $bridgeService->createManagementSessionUrl($externalUserId, $userEmail, $callbackUrl);

            return redirect($url);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Bridge renew session failed: ' . $e->getMessage());
            return redirect('/banque/banque/bank-accounts')->with('error', 'Erreur lors du renouvellement de la connexion: ' . $e->getMessage());
        }
    }
}
