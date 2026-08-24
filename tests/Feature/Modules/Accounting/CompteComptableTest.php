<?php

use App\Models\Accounting\CompteComptable;
use Database\Factories\Accounting\CompteComptableFactory;
use Database\Seeders\Accounting\PcgSeeder;

test('CompteComptable can be created via factory', function () {
    $compte = CompteComptable::factory()->create();

    expect($compte)->toBeInstanceOf(CompteComptable::class);
    expect($compte->numero)->toStartWith((string) $compte->classe);
    expect($compte->libelle)->not->toBeEmpty();
    expect($compte->classe)->toBeBetween(1, 8);
});

test('CompteComptable has correct fillable attributes', function () {
    $compte = new CompteComptable();

    expect($compte->getFillable())->toBe([
        'numero', 'libelle', 'classe', 'is_balance', 'parent_id',
    ]);
});

test('CompteComptable casts is_balance to boolean', function () {
    $compte = CompteComptable::factory()->create(['is_balance' => true]);

    expect($compte->is_balance)->toBeTrue();

    $compte2 = CompteComptable::factory()->create(['is_balance' => false]);

    expect($compte2->is_balance)->toBeFalse();
});

test('CompteComptable has parent relationship', function () {
    $parent = CompteComptable::factory()->classe1()->create();
    $child = CompteComptable::factory()->classe1()->create(['parent_id' => $parent->id]);

    expect($child->parent)->not->toBeNull();
    expect($child->parent->id)->toBe($parent->id);
});

test('CompteComptable has children relationship', function () {
    $parent = CompteComptable::factory()->classe1()->create(['numero' => '210000']);
    $child1 = CompteComptable::factory()->classe1()->create(['numero' => '211000', 'parent_id' => $parent->id]);
    $child2 = CompteComptable::factory()->classe1()->create(['numero' => '212000', 'parent_id' => $parent->id]);

    expect($parent->children)->toHaveCount(2);
    expect($parent->children->pluck('id'))->toContain($child1->id, $child2->id);
});

test('CompteComptable classe label returns correct string', function () {
    $classes = [
        1 => 'Comptes de ressources durables',
        2 => 'Immobilisations',
        3 => 'Stocks',
        4 => 'Tiers',
        5 => 'Financier',
        6 => 'Charges',
        7 => 'Produits',
        8 => 'Résultat',
    ];

    foreach ($classes as $classe => $expected) {
        $compte = CompteComptable::factory()->create(['classe' => $classe]);
        expect($compte->classe_label)->toBe($expected);
    }
});

test('CompteComptable full_label returns numero + libelle', function () {
    $compte = CompteComptable::factory()->create([
        'numero' => '411100',
        'libelle' => 'Clients',
    ]);

    expect($compte->full_label)->toBe('411100 - Clients');
});

test('CompteComptable scope deClasse filters correctly', function () {
    CompteComptable::factory()->classe4()->count(3)->create();
    CompteComptable::factory()->classe6()->count(2)->create();

    $classe4 = CompteComptable::deClasse(4)->count();
    $classe6 = CompteComptable::deClasse(6)->count();

    expect($classe4)->toBe(3);
    expect($classe6)->toBe(2);
});

test('CompteComptable scope balances filters correctly', function () {
    CompteComptable::factory()->balance()->count(3)->create();
    CompteComptable::factory()->count(2)->create(['is_balance' => false]);

    expect(CompteComptable::balances()->count())->toBe(3);
});

test('PCG seeder creates expected accounts', function () {
    $this->seed(PcgSeeder::class);

    $total = CompteComptable::count();
    expect($total)->toBeGreaterThan(100);

    $classe1 = CompteComptable::deClasse(1)->count();
    $classe2 = CompteComptable::deClasse(2)->count();
    $classe4 = CompteComptable::deClasse(4)->count();
    $classe6 = CompteComptable::deClasse(6)->count();
    $classe7 = CompteComptable::deClasse(7)->count();

    expect($classe1)->toBeGreaterThan(5);
    expect($classe2)->toBeGreaterThan(10);
    expect($classe4)->toBeGreaterThan(10);
    expect($classe6)->toBeGreaterThan(10);
    expect($classe7)->toBeGreaterThan(3);
});

test('PCG seeder is idempotent', function () {
    $this->seed(PcgSeeder::class);
    $count1 = CompteComptable::count();

    $this->seed(PcgSeeder::class);
    $count2 = CompteComptable::count();

    expect($count1)->toBe($count2);
});

test('PCG seeder creates all essential class 4 supplier/client accounts', function () {
    $this->seed(PcgSeeder::class);

    $essentials = [
        '401100', '411100', '411200',
        '442600', '445600', '445660',
    ];

    foreach ($essentials as $numero) {
        expect(CompteComptable::where('numero', $numero)->exists())->toBeTrue(
            "Account {$numero} should exist after seeding"
        );
    }
});

test('CompteComptable numero must be unique', function () {
    $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

    $compte = CompteComptable::factory()->create(['numero' => '411100']);
    CompteComptable::factory()->create(['numero' => '411100']);
});

test('CompteComptable factory creates distinct accounts', function () {
    $comptes = CompteComptable::factory()->count(20)->create();
    $numeros = $comptes->pluck('numero')->unique();

    expect($numeros)->toHaveCount(20);
});
