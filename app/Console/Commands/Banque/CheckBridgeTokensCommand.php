<?php

namespace App\Console\Commands\Banque;

use App\Models\Core\Company;
use App\Models\User;
use App\Services\Banque\BridgeApiService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckBridgeTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-bridge-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie l\'expiration DSP2 des connexions bancaires Bridge et notifie les administrateurs.';

    /**
     * Execute the console command.
     */
    public function handle(BridgeApiService $bridgeService)
    {
        // On récupère les entreprises ayant au moins un compte bancaire connecté à Bridge
        $companies = Company::whereHas('bankAccounts', function ($query) {
            $query->whereNotNull('bridge_account_id');
        })->get();

        $admins = User::admin()->get();
        if ($admins->isEmpty()) {
            $this->warn('Aucun administrateur trouvé pour recevoir les notifications.');

            return;
        }

        foreach ($companies as $company) {
            $this->info("Vérification des connexions pour l'entreprise ID: {$company->id}");

            try {
                $expiringItems = $bridgeService->checkItemsExpiration($company->id);

                if (! empty($expiringItems)) {
                    $this->warn('Trouvé '.count($expiringItems)." connexion(s) expirant bientôt pour l'entreprise {$company->id}.");

                    foreach ($expiringItems as $item) {
                        $itemId = $item['item_id'];

                        foreach ($admins as $admin) {
                            $cacheKey = "bridge_notify_{$admin->id}_{$company->id}_{$itemId}";

                            if (Cache::add($cacheKey, true, now()->addHours(24))) {
                                Notification::make()
                                    ->title('Authentification bancaire requise')
                                    ->body('Une ou plusieurs de vos connexions bancaires vont bientôt expirer (DSP2) ou nécessitent une action.')
                                    ->warning()
                                    ->actions([
                                        Action::make('renouveler')
                                            ->label('Renouveler l\'accès')
                                            ->url(route('bridge.renew', ['company_id' => $company->id]))
                                            ->button(),
                                    ])
                                    ->sendToDatabase($admin);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Erreur pour l'entreprise {$company->id}: ".$e->getMessage());
                Log::error('CheckBridgeTokensCommand failed: '.$e->getMessage());
            }
        }

        $this->info('Vérification terminée.');
    }
}
