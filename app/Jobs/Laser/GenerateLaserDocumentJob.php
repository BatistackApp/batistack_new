<?php

namespace App\Jobs\Laser;

use App\Services\Laser\LaserDocumentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateLaserDocumentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $namespace,
        public Model $model
    ) {}

    public function uniqueId(): string
    {
        return $this->namespace.':'.$this->model->getKey();
    }

    public function handle(): void
    {
        match ($this->namespace) {
            'laser_quote' => app(LaserDocumentationService::class)->generateQuotePdf($this->model),
            default => throw new \InvalidArgumentException("Invalid namespace: {$this->namespace}"),
        };
    }
}
