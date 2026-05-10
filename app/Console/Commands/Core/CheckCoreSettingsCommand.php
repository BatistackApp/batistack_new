<?php

namespace App\Console\Commands\Core;

use App\Models\Core\Setting;
use Illuminate\Console\Command;

class CheckCoreSettingsCommand extends Command
{
    protected $signature = 'app:core-check';

    protected $description = "Vérifie l'intégrité des paramètres de base de l'ERP";

    public function handle(): void
    {
        $this->info('Analyse des paramètres Batistack...');

        $count = Setting::count();
        $this->line("Nombre de paramètres enregistrés : {$count}");

        if ($count === 0) {
            $this->error('Aucun paramètre trouvé. Veuillez lancer le seeder.');
        } else {
            $this->info('Configuration Core valide.');
        }
    }
}
