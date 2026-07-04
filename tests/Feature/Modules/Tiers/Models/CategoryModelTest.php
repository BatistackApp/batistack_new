<?php

namespace Tests\Feature\Modules\Tiers\Models;

use App\Models\Tiers\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Category - Scopes', function () {
    test('scope search() recherche par nom', function () {
        Category::factory()->create(['name' => 'Construction']);
        Category::factory()->create(['name' => 'Plomberie']);

        $result = Category::search('Const')->get();

        expect($result->count())->toBe(1)
            ->and($result->first()->name)->toBe('Construction');
    });

    test('scope orderByName() trie alphabétiquement', function () {
        Category::factory()->create(['name' => 'Zebre']);
        Category::factory()->create(['name' => 'Alpha']);
        Category::factory()->create(['name' => 'Charlie']);

        $asc = Category::orderByName('asc')->get();
        expect($asc->first()->name)->toBe('Alpha')
            ->and($asc->last()->name)->toBe('Zebre');

        $desc = Category::orderByName('desc')->get();
        expect($desc->first()->name)->toBe('Zebre')
            ->and($desc->last()->name)->toBe('Alpha');
    });
});

describe('Category - Static Methods', function () {
    test('byName() récupère une catégorie par nom', function () {
        $category = Category::factory()->create(['name' => 'Électricité']);

        $result = Category::byName('Électricité');
        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($category->id);

        $notFound = Category::byName('Inconnu');
        expect($notFound)->toBeNull();
    });

    test('nameExists() vérifie si une catégorie existe', function () {
        Category::factory()->create(['name' => 'Peinture']);

        expect(Category::nameExists('Peinture'))->toBeTrue()
            ->and(Category::nameExists('Placo'))->toBeFalse();
    });
});
