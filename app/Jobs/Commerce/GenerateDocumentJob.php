<?php

namespace App\Jobs\Commerce;

use App\Services\Commerce\CommerceDocumentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $namespace,
        public Model $model
    ) {}

    public function handle(): void
    {
        $pdf_path = match ($this->namespace) {
            'quote' => app(CommerceDocumentationService::class)->generateQuotePdf($this->model),
            'invoice' => app(CommerceDocumentationService::class)->generateInvoicePdf($this->model),
        };

        $this->model->updateQuietly([
            'pdf_path' => $pdf_path,
        ]);
    }
}
