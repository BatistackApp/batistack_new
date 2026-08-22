<?php

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Vision3D\BimModel;
use App\Models\Vision3D\BimQuantity;
use App\Services\Vision3D\BimStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Queue::fake();
});

it('can upload and store bim model', function () {
    Storage::fake('public');

    $chantier = Chantier::factory()->create();
    $file = UploadedFile::fake()->create('test-model.ifc', 1024, 'application/octet-stream');

    $service = new BimStorageService;
    $bimModel = $service->storeModel($file, Chantier::class, $chantier->id, 'Maison 3D');

    expect($bimModel)->toBeInstanceOf(BimModel::class)
        ->name->toBe('Maison 3D')
        ->format->toBe('ifc');

    Storage::disk('public')->assertExists($bimModel->file_path);

    expect($chantier->bimModels)->toHaveCount(1);
    expect($chantier->bimModels->first()->id)->toBe($bimModel->id);
});

it('can delete bim model and file', function () {
    Storage::fake('public');

    $chantier = Chantier::factory()->create();
    $file = UploadedFile::fake()->create('test-model.ifc', 1024, 'application/octet-stream');

    $service = new BimStorageService;
    $bimModel = $service->storeModel($file, Chantier::class, $chantier->id, 'Maison 3D');

    Storage::disk('public')->assertExists($bimModel->file_path);

    $service->deleteModel($bimModel);

    $this->assertDatabaseMissing('bim_models', ['id' => $bimModel->id]);
    Storage::disk('public')->assertMissing($bimModel->file_path);
});

it('can create a revision of a bim model', function () {
    $parent = BimModel::create([
        'name' => 'V1',
        'file_path' => 'v1.ifc',
        'format' => 'ifc',
        'version' => 1,
    ]);

    $child = BimModel::create([
        'name' => 'V2',
        'file_path' => 'v2.ifc',
        'format' => 'ifc',
        'parent_id' => $parent->id,
        'version' => 2,
    ]);

    expect($child->parent->id)->toBe($parent->id)
        ->and($parent->children)->toHaveCount(1)
        ->and($parent->children->first()->id)->toBe($child->id);
});

it('can create multiple annotations for clashes', function () {
    $bimModel = BimModel::create([
        'name' => 'V1',
        'file_path' => 'v1.ifc',
        'format' => 'ifc',
        'version' => 1,
    ]);

    $bimModel->annotations()->create([
        'title' => 'Collision détectée',
        'description' => 'Collision automatique',
        'position_x' => 10,
        'position_y' => 20,
        'position_z' => 30,
    ]);

    $bimModel->annotations()->create([
        'title' => 'Collision détectée 2',
        'description' => 'Collision automatique',
        'position_x' => 15,
        'position_y' => 25,
        'position_z' => 35,
    ]);

    expect($bimModel->annotations)->toHaveCount(2);
    expect($bimModel->annotations->first()->title)->toBe('Collision détectée');
});

it('can have multiple quantities (BOM) linked to items', function () {
    $bimModel = BimModel::factory()->create();
    $item = Item::factory()->create(['type' => ItemType::STOCKABLE, 'purchase_price' => 10]);

    $quantity = BimQuantity::create([
        'bim_model_id' => $bimModel->id,
        'item_id' => $item->id,
        'element_name' => 'MUR-01',
        'unit' => 'm2',
        'quantity_required' => 12.5,
    ]);

    expect($bimModel->quantities)->toHaveCount(1)
        ->and($quantity->bimModel->id)->toBe($bimModel->id)
        ->and($quantity->item->id)->toBe($item->id)
        ->and((float) $quantity->quantity_required)->toBe(12.5);
});
