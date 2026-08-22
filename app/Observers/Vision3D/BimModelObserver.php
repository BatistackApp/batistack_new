<?php

namespace App\Observers\Vision3D;

use App\Jobs\GenerateBimThumbnailJob;
use App\Models\Vision3D\BimModel;

class BimModelObserver
{
    public function created(BimModel $model): void
    {
        $this->dispatchThumbnailJob($model);
    }

    public function updated(BimModel $model): void
    {
        if ($model->wasChanged('file_path') && $model->file_path) {
            $this->dispatchThumbnailJob($model);
        }
    }

    private function dispatchThumbnailJob(BimModel $model): void
    {
        if (! $model->file_path || in_array($model->format, ['obj', 'stl'])) {
            return;
        }

        GenerateBimThumbnailJob::dispatch($model);
    }
}
