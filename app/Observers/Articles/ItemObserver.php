<?php

namespace App\Observers\Articles;

use App\Enums\Articles\ItemType;
use App\Jobs\Articles\RecalculateWorkCostsJob;
use App\Models\Articles\Item;
use Exception;
use Illuminate\Support\Str;
use Log;

class ItemObserver
{
    /**
     * Valider avant création
     *
     * @throws Exception
     */
    public function creating(Item $item): void
    {
        $this->validate($item);
    }

    /**
     * Valider avant mise à jour
     *
     * @throws Exception
     */
    public function updating(Item $item): void
    {
        $this->validate($item);
    }

    /**
     * Normaliser avant sauvegarde
     */
    public function saving(Item $item): void
    {
        // Normalisation de la référence en majuscules
        if ($item->isDirty('reference')) {
            $item->reference = Str::upper(trim($item->reference ?? ''));
        }

        // Normalisation du nom (Majuscule pour la première lettre)
        if ($item->isDirty('name')) {
            $item->name = Str::ucfirst(trim($item->name ?? ''));
        }
    }

    /**
     * Après création: logging
     */
    public function created(Item $item): void
    {
        Log::info('Article créé', [
            'id' => $item->id,
            'reference' => $item->reference,
            'name' => $item->name,
            'type' => $item->type,
        ]);
    }

    /**
     * Après mise à jour: recalculer coûts si prix change
     */
    public function updated(Item $item): void
    {
        // Si le prix d'achat change, recalculer les ouvrages qui utilisent cet article
        if ($item->isDirty('purchase_price') && $item->type !== ItemType::WORK) {
            RecalculateWorkCostsJob::dispatch($item);
            Log::info('Recalcul coûts ouvrages déclenché', [
                'item_id' => $item->id,
                'new_purchase_price' => $item->purchase_price,
            ]);
        }

        // Logging des changements importants
        if ($item->wasChanged(['selling_price', 'is_active'])) {
            Log::info('Article mis à jour', [
                'id' => $item->id,
                'changes' => $item->getChanges(),
            ]);
        }
    }

    /**
     * Avant suppression: vérifier que n'est pas utilisé
     *
     * @throws Exception
     */
    public function deleting(Item $item): bool
    {
        // Vérifier usage dans les stocks
        if ($item->stocks()->exists()) {
            throw new Exception("Impossible de supprimer: l'article est en stock");
        }

        // Vérifier usage dans les compositions
        if ($item->components()->exists()) {
            throw new Exception("Impossible de supprimer: l'article est utilisé dans d'autres articles");
        }

        return true;
    }

    /**
     * Après suppression: logging
     */
    public function deleted(Item $item): void
    {
        Log::warning('Article supprimé', [
            'id' => $item->id,
            'reference' => $item->reference,
            'name' => $item->name,
        ]);
    }

    /**
     * Valider les données de l'article
     *
     * @throws Exception
     */
    private function validate(Item $item): void
    {
        // Valider référence unique
        if ($item->isDirty('reference')) {
            $exists = Item::where('reference', $item->reference)
                ->where('id', '!=', $item->id)
                ->exists();

            if ($exists) {
                throw new Exception("La référence '{$item->reference}' est déjà utilisée");
            }
        }

        // Valider que la référence n'est pas vide
        if (empty($item->reference)) {
            throw new Exception('La référence est obligatoire');
        }

        // Valider que le nom n'est pas vide
        if (empty($item->name)) {
            throw new Exception('Le nom est obligatoire');
        }

        // Valider prices >= 0
        if ($item->purchase_price < 0) {
            throw new Exception('Le prix d\'achat ne peut pas être négatif');
        }

        if ($item->selling_price < 0) {
            throw new Exception('Le prix de vente ne peut pas être négatif');
        }

        // Valider min_stock >= 0
        if ($item->min_stock < 0) {
            throw new Exception('Le stock minimum ne peut pas être négatif');
        }

        // Valider type valide
        if (! in_array($item->type, ItemType::cases())) {
            throw new Exception('Le type d\'article est invalide');
        }

        // Valider que unit existe si présent
        if ($item->unit_id && ! \DB::table('units')->where('id', $item->unit_id)->exists()) {
            throw new Exception('L\'unité spécifiée n\'existe pas');
        }

        // Valider que vat_rate existe si présent
        if ($item->vat_rate_id && ! \DB::table('vat_rates')->where('id', $item->vat_rate_id)->exists()) {
            throw new Exception('La TVA spécifiée n\'existe pas');
        }

        // Valider que parent existe si présent
        if ($item->parent_id && ! \DB::table('items')->where('id', $item->parent_id)->exists()) {
            throw new Exception('L\'article parent n\'existe pas');
        }
    }
}
