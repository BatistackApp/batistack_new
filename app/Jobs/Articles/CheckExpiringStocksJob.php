<?php

namespace App\Jobs\Articles;

use App\Models\Articles\StockMouvement;
use App\Models\User;
use App\Notifications\Articles\StockExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CheckExpiringStocksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // On récupère tous les mouvements d'entrée qui expirent dans les 30 prochains jours
        // et qui n'ont pas encore expiré
        $mouvements = StockMouvement::with('stock.item.unit')
            ->incoming()
            ->whereNotNull('batch_number')
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [Carbon::today(), Carbon::today()->addDays(30)])
            ->get()
            // Une seule alerte par lot (stock_id + batch_number)
            ->unique(fn ($mouvement) => $mouvement->stock_id.'|'.$mouvement->batch_number);

        $admins = User::admin()->get();
        if ($admins->isEmpty()) {
            return;
        }

        foreach ($mouvements as $mouvement) {
            $remaining = StockMouvement::getRemainingBatchQuantity($mouvement->stock_id, $mouvement->batch_number);

            if ($remaining > 0) {
                foreach ($admins as $admin) {
                    $admin->notify(new StockExpiringNotification($mouvement, $remaining));
                }
            }
        }
    }
}
