<?php

namespace App\Observers\Articles;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use Illuminate\Support\Str;

class ItemObserver
{
    public function updated(Item $item): void
    {
        // Si le prix d'achat change, on doit recalculer tous les ouvrages qui utilisent cet article
        if ($item->isDirty('purchase_price') && $item->type !== ItemType::WORK) {
            RecalculateWorkCostsJob::dispatch($item);
        }
    }

    public function saving(Item $item): void
    {
        // Normalisation de la référence en majuscules
        if ($item->isDirty('reference')) {
            $item->reference = Str::upper($item->reference);
        }

        // Normalisation du nom (Majuscule pour la première lettre)
        if ($item->isDirty('name')) {
            $item->name = Str::ucfirst($item->name);
        }
    }
}
