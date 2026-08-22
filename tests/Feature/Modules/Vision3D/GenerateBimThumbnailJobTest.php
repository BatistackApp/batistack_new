<?php

use App\Jobs\GenerateBimThumbnailJob;
use App\Models\Vision3D\BimModel;
use Illuminate\Support\Facades\Storage;

it('generates a thumbnail successfully', function () {
    Storage::fake('public');

    $model = BimModel::create([
        'name' => 'Test',
        'file_path' => 'models/test.ifc',
        'format' => 'ifc',
        'version' => 1,
    ]);

    $job = \Mockery::mock(GenerateBimThumbnailJob::class)->makePartial();
    $job->shouldReceive('renderScreenshot')->once()->andReturnNull();

    $job->handle();

    $model->refresh();
    expect($model->thumbnail_path)->not->toBeNull()
        ->and($model->thumbnail_path)->toContain('bim-thumbnails/' . $model->id . '_');
});

it('skips update when model no longer exists', function () {
    $model = BimModel::create([
        'name' => 'Test',
        'file_path' => 'models/test.ifc',
        'format' => 'ifc',
        'version' => 1,
    ]);

    $job = \Mockery::mock(GenerateBimThumbnailJob::class)->makePartial();
    $job->shouldReceive('renderScreenshot')->never();

    $model->delete();

    $job->handle();

    $this->assertTrue(true);
});

it('skips update when file_path changed since job was queued', function () {
    $model = BimModel::create([
        'name' => 'Test',
        'file_path' => 'models/test.ifc',
        'format' => 'ifc',
        'version' => 1,
    ]);

    $job = \Mockery::mock(GenerateBimThumbnailJob::class)->makePartial();
    $job->shouldReceive('renderScreenshot')->never();

    $model->update(['file_path' => 'models/test-v2.ifc']);

    $job->handle();

    $model->refresh();
    expect($model->thumbnail_path)->toBeNull();
});

it('stores the thumbnail in the correct directory', function () {
    Storage::fake('public');

    $model = BimModel::create([
        'name' => 'Test',
        'file_path' => 'models/test.ifc',
        'format' => 'ifc',
        'version' => 1,
    ]);

    $job = \Mockery::mock(GenerateBimThumbnailJob::class)->makePartial();
    $job->shouldReceive('renderScreenshot')->once()->andReturnNull();

    $job->handle();

    Storage::disk('public')->assertExists('bim-thumbnails');
});

it('captures filePath at construction time', function () {
    $model = BimModel::create([
        'name' => 'Test',
        'file_path' => 'models/test.ifc',
        'format' => 'ifc',
        'version' => 1,
    ]);

    $job = new GenerateBimThumbnailJob($model);

    expect($job->filePath)->toBe('models/test.ifc')
        ->and($job->bimModel->id)->toBe($model->id);
});
