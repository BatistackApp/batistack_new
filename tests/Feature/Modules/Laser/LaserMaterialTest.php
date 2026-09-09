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

it('casts attributes correctly', function () {
    $material = LaserMaterial::factory()->create([
        'density_kg_m3' => 7850.00,
        'price_per_kg' => 1.2000,
        'price_per_meter' => 0.8000,
        'min_thickness_mm' => 0.50,
        'max_thickness_mm' => 25.00,
        'is_active' => true,
    ]);

    expect($material->density_kg_m3)->toBe('7850.00')
        ->and($material->price_per_kg)->toBe('1.2000')
        ->and($material->price_per_meter)->toBe('0.8000')
        ->and($material->min_thickness_mm)->toBe('0.50')
        ->and($material->max_thickness_mm)->toBe('25.00')
        ->and($material->is_active)->toBeTrue();
});

it('validates required fields', function () {
    $data = [
        'name' => '',
        'density_kg_m3' => '',
        'price_per_kg' => '',
        'price_per_meter' => '',
        'min_thickness_mm' => '',
        'max_thickness_mm' => '',
    ];

    $rules = [
        'name' => ['required', 'string', 'max:255'],
        'density_kg_m3' => ['required', 'numeric', 'min:0'],
        'price_per_kg' => ['required', 'numeric', 'min:0'],
        'price_per_meter' => ['required', 'numeric', 'min:0'],
        'min_thickness_mm' => ['required', 'numeric', 'min:0'],
        'max_thickness_mm' => ['required', 'numeric', 'min:0'],
    ];

    $validator = \Illuminate\Support\Facades\Validator::make($data, $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue()
        ->and($validator->errors()->has('density_kg_m3'))->toBeTrue()
        ->and($validator->errors()->has('price_per_kg'))->toBeTrue()
        ->and($validator->errors()->has('price_per_meter'))->toBeTrue()
        ->and($validator->errors()->has('min_thickness_mm'))->toBeTrue()
        ->and($validator->errors()->has('max_thickness_mm'))->toBeTrue();
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
