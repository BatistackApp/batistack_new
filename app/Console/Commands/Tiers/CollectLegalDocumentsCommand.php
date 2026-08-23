<?php

namespace App\Console\Commands\Tiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Jobs\Tiers\CollectLegalDocumentsJob;
use App\Models\Tiers\ThirdParty;
use Illuminate\Console\Command;

class CollectLegalDocumentsCommand extends Command
{
    protected $signature = 'tiers:collect-legal-documents {--siren= : SIREN du tiers à collecter} {--all : Collecter pour tous les sous-traitants et clients actifs}';

    protected $description = 'Lance la collecte automatique des documents légaux (URSSAF + RNE) via l\'API Entreprise';

    public function handle(): int
    {
        $siren = $this->option('siren');
        $all = $this->option('all');

        if (! $siren && ! $all) {
            $this->error('Veuillez spécifier --siren=xxx ou --all.');

            return self::FAILURE;
        }

        if ($siren) {
            $thirdParty = ThirdParty::where('siren', $siren)->first();

            if (! $thirdParty) {
                $this->error("Aucun tiers trouvé avec le SIREN {$siren}.");

                return self::FAILURE;
            }

            CollectLegalDocumentsJob::dispatch($thirdParty);
            $this->info("Collecte lancée pour le tiers \"{$thirdParty->name}\" (SIREN: {$siren}).");

            return self::SUCCESS;
        }

        $thirdParties = ThirdParty::whereIn('type', [ThirdPartyType::SUBCONTRACTOR, ThirdPartyType::CLIENT])
            ->whereNotNull('siren')
            ->where('is_active', true)
            ->get();

        if ($thirdParties->isEmpty()) {
            $this->info('Aucun sous-traitant ou client actif avec un SIREN trouvé.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($thirdParties as $thirdParty) {
            CollectLegalDocumentsJob::dispatch($thirdParty);
            $count++;
        }

        $this->info("Collecte lancée pour {$count} tiers.");

        return self::SUCCESS;
    }
}
