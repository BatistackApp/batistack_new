<?php

namespace App\Jobs\Core;

use App\Services\Commerce\CommerceDocumentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $namespace, public Model $model) {}

    public function handle(): void
    {
        $pdfPath = match ($this->namespace) {
            'invoice' => app(CommerceDocumentationService::class)->generateInvoicePdf($this->model),
            'quote' => app(CommerceDocumentationService::class)->generateQuotePdf($this->model),
        };
    }
}
