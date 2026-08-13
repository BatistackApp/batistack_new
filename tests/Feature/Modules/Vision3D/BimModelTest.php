<?php

use App\Models\Chantiers\Chantier;
use App\Models\Vision3D\BimModel;
use App\Services\Vision3D\BimStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('can upload and store bim model', function () {
    Storage::fake('public');

    $chantier = Chantier::factory()->create();
    $file = UploadedFile::fake()->create('test-model.ifc', 1024, 'application/octet-stream');
    
    $service = new BimStorageService();
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
    
    $service = new BimStorageService();
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
