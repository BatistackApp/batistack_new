<?php

namespace App\Observers\Articles;

use App\Models\Articles\StockMouvement;
use Illuminate\Support\Facades\Auth;

class StockMouvementObserver
{
    public function created(StockMouvement $stock): void
    {
        if ($stock->quantity != 0) {
            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => Auth::id(),
                'type' => 'in',
                'quantity_before' => 0,
                'quantity_delta' => $stock->quantity,
                'quantity_after' => $stock->quantity,
                'description' => 'Initialisation du stock.',
            ]);
        }
    }

    public function updated(StockMouvement $stock): void
    {
        // On ne log que si la quantité a changé
        if ($stock->isDirty('quantity')) {
            $before = (float) $stock->getOriginal('quantity');
            $after = (float) $stock->quantity;
            $delta = $after - $before;

            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => Auth::id(),
                'type' => $delta > 0 ? 'in' : 'out',
                'quantity_before' => $before,
                'quantity_delta' => $delta,
                'quantity_after' => $after,
                'description' => "Mise à jour automatique du stock via l'application.",
            ]);
        }
    }
}
