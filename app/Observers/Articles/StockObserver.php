<?php

namespace App\Observers\Articles;

use App\Models\Articles\Stock;
use App\Models\User;
use App\Notifications\Articles\LowStockNotification;
use Illuminate\Support\Facades\Notification;

class StockObserver
{
    public function saved(Stock $stock): void
    {
        // Vérification du seuil de stock minimum
        if ($stock->quantity <= $stock->min_threshold) {
            // On récupère les utilisateurs ayant la permission de gestion des stocks (ou admin)
            $usersToNotify = User::where('is_admin', true)->get(); // À filtrer par rôle dans une étape ultérieure

            Notification::send($usersToNotify, new LowStockNotification($stock));
        }
    }
}
