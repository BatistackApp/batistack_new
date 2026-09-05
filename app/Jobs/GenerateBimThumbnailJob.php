<?php

namespace App\Jobs;

use App\Models\Vision3D\BimModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class GenerateBimThumbnailJob implements ShouldQueue
{
    use Queueable;

    public BimModel $bimModel;

    public string $filePath;

    public function __construct(BimModel $bimModel)
    {
        $this->bimModel = $bimModel;
        $this->filePath = $bimModel->file_path;
    }

    public function handle(): void
    {
        $model = BimModel::find($this->bimModel->id);

        if (! $model || $model->file_path !== $this->filePath) {
            return;
        }

        $url = route('bim-viewer.headless', ['id' => $model->id]);
        $filename = 'bim-thumbnails/'.$model->id.'_'.Str::random(8).'.png';

        if (! Storage::disk('public')->exists('bim-thumbnails')) {
            Storage::disk('public')->makeDirectory('bim-thumbnails');
        }

        $fullPath = Storage::disk('public')->path($filename);

        $this->renderScreenshot($url, $fullPath);

        $model->update([
            'thumbnail_path' => $filename,
        ]);
    }

    protected function renderScreenshot(string $url, string $fullPath): void
    {
        $browsershot = Browsershot::url($url)
            ->waitUntilNetworkIdle()
            ->setDelay(8000)
            ->windowSize(800, 600);

        if (env('CI')) {
            $browsershot->addChromiumArguments(['no-sandbox']);
        }

        $browsershot->save($fullPath);
    }
}
