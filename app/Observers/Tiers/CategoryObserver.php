<?php

namespace App\Observers\Tiers;

use App\Models\Tiers\Category;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryObserver
{
    /**
     * Valider avant création
     *
     * @throws Exception
     */
    public function creating(Category $category): void
    {
        $this->validateName($category);
    }

    /**
     * Valider avant mise à jour
     *
     * @throws Exception
     */
    public function updating(Category $category): void
    {
        $this->validateName($category);
    }

    /**
     * Invalidate cache après création
     */
    public function created(Category $category): void
    {
        Cache::forget('tiers_categories_all');
        Log::info('Category created', [
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }

    /**
     * Invalidate cache après mise à jour
     */
    public function updated(Category $category): void
    {
        Cache::forget('tiers_categories_all');
        Cache::forget("tiers_category_{$category->id}");
        Log::info('Category updated', [
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }

    /**
     * Empêcher suppression si utilisée
     * @throws Exception
     */
    public function deleting(Category $category): bool
    {
        // Vérifier si utilisée par des tiers
        $thirdPartyCount = \DB::table('category_third_party')
            ->where('category_id', $category->id)
            ->count();

        if ($thirdPartyCount > 0) {
            Log::warning('Tentative suppression catégorie utilisée', [
                'category_id' => $category->id,
                'third_party_count' => $thirdPartyCount,
            ]);
            throw new Exception("Impossible de supprimer cette catégorie ({$thirdPartyCount} tiers l'utilisent)");
        }

        return true;
    }

    /**
     * Invalidate cache après suppression
     */
    public function deleted(Category $category): void
    {
        Cache::forget('tiers_categories_all');
        Cache::forget("tiers_category_{$category->id}");
        Log::info('Category deleted', [
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }

    /**
     * Valider le nom de la catégorie
     * @throws Exception
     */
    private function validateName(Category $category): void
    {
        if (empty($category->name)) {
            throw new Exception('Le nom de la catégorie est obligatoire');
        }

        // Vérifier l'unicité du nom
        $query = Category::where('name', $category->name);

        if ($category->exists) {
            $query->where('id', '!=', $category->id);
        }

        if ($query->exists()) {
            throw new Exception("Une catégorie avec le nom '{$category->name}' existe déjà");
        }
    }
}
