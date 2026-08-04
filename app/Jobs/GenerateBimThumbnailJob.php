<?php

namespace App\Jobs;

use App\Models\Vision3D\BimModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Str;

class GenerateBimThumbnailJob implements ShouldQueue
{
    use Queueable;

    public $bimModel;

    /**
     * Create a new job instance.
     */
    public function __construct(BimModel $bimModel)
    {
        $this->bimModel = $bimModel;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = route('bim-viewer.headless', ['id' => $this->bimModel->id]);
        $filename = 'bim-thumbnails/' . $this->bimModel->id . '_' . Str::random(8) . '.png';
        
        if (!Storage::disk('public')->exists('bim-thumbnails')) {
            Storage::disk('public')->makeDirectory('bim-thumbnails');
        }
        
        $fullPath = Storage::disk('public')->path($filename);

        Browsershot::url($url)
            ->waitUntilNetworkIdle()
            ->setDelay(8000) // Wait extra for WebGL loading
            ->windowSize(800, 600)
            ->save($fullPath);

        $this->bimModel->update([
            'thumbnail_path' => $filename,
        ]);
    }
}
