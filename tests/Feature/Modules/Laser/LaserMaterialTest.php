<?php

use App\Models\Laser\LaserMaterial;
use Database\Seeders\LaserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a laser material via factory', function () {
    $material = LaserMaterial::factory()->create();

    expect($material)->toBeInstanceOf(LaserMaterial::class)
        ->and($material->name)->not->toBeEmpty()
        ->and($material->density_kg_m3)->toBeGreaterThan(0)
        ->and($material->price_per_kg)->toBeGreaterThan(0)
        ->and($material->price_per_meter)->toBeGreaterThan(0)
        ->and($material->is_active)->toBeTrue();
});

it('validates required fields', function () {
    $response = $this->actingAs(\App\Models\User::factory()->create(['is_admin' => true]))
        ->post('/laser/laser-materials', []);

    $response->assertSessionHasErrors([
        'name',
        'density_kg_m3',
        'price_per_kg',
        'price_per_meter',
        'min_thickness_mm',
        'max_thickness_mm',
    ]);
});

it('seeds laser materials correctly', function () {
    $this->seed(LaserSeeder::class);

    expect(LaserMaterial::count())->toBe(4);

    expect(LaserMaterial::where('name', 'Acier S235')->exists())->toBeTrue()
        ->and(LaserMaterial::where('name', 'Inox 304')->exists())->toBeTrue()
        ->and(LaserMaterial::where('name', 'Inox 316')->exists())->toBeTrue()
        ->and(LaserMaterial::where('name', 'Aluminium')->exists())->toBeTrue();
});

it('seeder is idempotent', function () {
    $this->seed(LaserSeeder::class);
    $this->seed(LaserSeeder::class);

    expect(LaserMaterial::count())->toBe(4);
});

it('renders laser materials list for admin', function () {
    LaserMaterial::factory()->count(3)->create();

    $user = \App\Models\User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get('/laser/laser-materials')
        ->assertOk();
});
