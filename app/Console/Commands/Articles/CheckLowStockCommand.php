<?php

namespace App\Console\Commands\Articles;

use App\Models\Articles\Stock;
use App\Models\User;
use App\Notifications\Articles\LowStockNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CheckLowStockCommand extends Command
{
    protected $signature = 'articles:check-stocks';

    protected $description = 'Vérifie les articles sous le seuil de sécurité et envoie les notifications';

    public function handle(): int
    {
        $this->info('Analyse des stocks en cours...');

        $criticalStocks = Stock::whereColumn('quantity', '<=', 'min_threshold')
            ->with(['item', 'warehouse'])
            ->get();

        if ($criticalStocks->isEmpty()) {
            $this->info('Aucun stock critique détecté.');
            return CommandAlias::SUCCESS;
        }

        $this->warn("{$criticalStocks->count()} alertes détectées.");

        $users = User::admin()->get(); // À filtrer par rôle/permission

        foreach ($criticalStocks as $stock) {
            Notification::send($users, new LowStockNotification($stock));
            $this->line("- Alerte : {$stock->item->name} dans {$stock->warehouse->name}");
        }

        $this->info('Notifications envoyées.');
        return CommandAlias::SUCCESS;
    }
}
