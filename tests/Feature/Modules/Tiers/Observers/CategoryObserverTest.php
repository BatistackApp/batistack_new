<?php

namespace Tests\Feature\Modules\Tiers\Observers;

use App\Models\Core\Company;
use App\Models\Tiers\Category;
use App\Models\Tiers\ThirdParty;
use Exception;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Company::factory()->create();
});

describe('CategoryObserver - creating()', function () {
    test('accepte une catégorie valide', function () {
        Category::create([
            'name' => 'VIP Clients',
            'color' => '#FF0000',
            'icon' => 'star',
        ]);

        expect(Category::count())->toBe(1);
    });

    test('rejette si nom vide', function () {
        expect(function () {
            Category::create([
                'name' => '',
                'color' => '#FF0000',
            ]);
        })->toThrow(Exception::class, 'obligatoire');
    });

    test('rejette si nom null', function () {
        expect(function () {
            Category::create([
                'name' => null,
                'color' => '#FF0000',
            ]);
        })->toThrow(Exception::class, 'obligatoire');
    });

    test('rejette si nom déjà utilisé', function () {
        Category::create(['name' => 'VIP Clients']);

        expect(function () {
            Category::create(['name' => 'VIP Clients']);
        })->toThrow(Exception::class, 'existe déjà');
    });

    test('accepte noms différents', function () {
        Category::create(['name' => 'VIP Clients']);
        Category::create(['name' => 'Standard Clients']);

        expect(Category::count())->toBe(2);
    });
});

describe('CategoryObserver - updating()', function () {
    test('permet update si nom unique', function () {
        $category = Category::create(['name' => 'Category 1']);

        $category->update(['name' => 'Category 1 Updated']);

        expect($category->fresh()->name)->toBe('Category 1 Updated');
    });

    test('rejette update si nouveau nom existe', function () {
        Category::create(['name' => 'Category 1']);
        $category2 = Category::create(['name' => 'Category 2']);

        expect(function () use ($category2) {
            $category2->update(['name' => 'Category 1']);
        })->toThrow(Exception::class, 'existe déjà');
    });

    test('accepte update du même nom', function () {
        $category = Category::create(['name' => 'VIP']);

        $category->update(['color' => '#0000FF']);

        expect($category->fresh()->name)->toBe('VIP');
    });
});

describe('CategoryObserver - deleting()', function () {
    test('accepte suppression si catégorie non utilisée', function () {
        $category = Category::create(['name' => 'Unused']);

        $category->delete();

        expect(Category::count())->toBe(0);
    });

    test('empêche suppression si utilisée par un tiers', function () {
        $category = Category::create(['name' => 'VIP Clients']);
        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->categories()->attach($category);

        expect(function () use ($category) {
            $category->delete();
        })->toThrow(Exception::class, 'Impossible');
    });

    test('empêche suppression si utilisée par plusieurs tiers', function () {
        $category = Category::create(['name' => 'Active']);

        ThirdParty::factory(3)->create()
            ->each(function ($tp) use ($category) {
                $tp->categories()->attach($category);
            });

        expect(function () use ($category) {
            $category->delete();
        })->toThrow(Exception::class, '3 tiers');
    });

    test('accepte suppression après détachement des tiers', function () {
        $category = Category::create(['name' => 'To Delete']);
        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->categories()->attach($category);

        $thirdParty->categories()->detach($category);
        $category->delete();

        expect(Category::count())->toBe(0);
    });
});

describe('CategoryObserver - Cache Invalidation', function () {
    test('invalide cache après création', function () {
        Cache::shouldReceive('forget')
            ->with('tiers_categories_all')
            ->once();

        Category::create(['name' => 'Test']);
    });

    test('invalide cache après update', function () {
        $category = Category::create(['name' => 'Test']);

        Cache::shouldReceive('forget')
            ->with(\Mockery::any())
            ->atLeast()
            ->once();

        $category->update(['name' => 'Updated']);
    });

    test('invalide cache après suppression', function () {
        $category = Category::create(['name' => 'Test']);

        Cache::shouldReceive('forget')
            ->with(\Mockery::any())
            ->atLeast()
            ->once();

        $category->delete();
    });
});

describe('CategoryObserver - Intégration', function () {
    test('crée catégorie et l\'utilise', function () {
        $category = Category::create([
            'name' => 'Premium',
            'color' => '#FFD700',
            'icon' => 'diamond',
        ]);

        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->categories()->attach($category);

        expect($thirdParty->categories->count())->toBe(1)
            ->and($thirdParty->categories->first()->name)->toBe('Premium');
    });

    test('gère plusieurs catégories par tiers', function () {
        $premium = Category::create(['name' => 'Premium']);
        $vip = Category::create(['name' => 'VIP']);
        $standard = Category::create(['name' => 'Standard']);

        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->categories()->attach([$premium, $vip, $standard]);

        expect($thirdParty->categories->count())->toBe(3);
    });

    test('flux complet: créer, utiliser, ne pas pouvoir supprimer', function () {
        $category = Category::create(['name' => 'Clients Importants']);
        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->categories()->attach($category);

        expect(function () use ($category) {
            $category->delete();
        })->toThrow(Exception::class, 'Impossible');
    });

    test('flux complet: créer, utiliser, supprimer après détachement', function () {
        $category = Category::create(['name' => 'Temporary']);
        $thirdParty = ThirdParty::factory()->create();
        $thirdParty->categories()->attach($category);

        expect(Category::count())->toBe(1);

        $thirdParty->categories()->detach();
        $category->delete();

        expect(Category::count())->toBe(0);
    });
});
