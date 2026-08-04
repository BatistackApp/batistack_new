<?php

use App\Jobs\GenerateBimThumbnailJob;
use App\Models\Vision3D\BimModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;

it('dispatches the GenerateBimThumbnailJob', function () {
    Queue::fake();

    $bimModel = BimModel::create([
        'name' => 'Test Model',
        'file_path' => 'models/test.ifc',
        'format' => 'ifc',
        'file_size' => 2048,
        'version' => 1,
        'modelable_id' => 1,
        'modelable_type' => 'App\Models\Articles\Item',
    ]);

    GenerateBimThumbnailJob::dispatch($bimModel);

    Queue::assertPushed(GenerateBimThumbnailJob::class, function ($job) use ($bimModel) {
        return $job->bimModel->id === $bimModel->id;
    });
});
