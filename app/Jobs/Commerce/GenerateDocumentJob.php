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
            'order' => app(CommerceDocumentationService::class)->generateOrderPdf($this->model),
            'delivery_note' => app(CommerceDocumentationService::class)->generateDeliveryNotePdf($this->model),
            'invoice' => app(CommerceDocumentationService::class)->generateInvoicePdf($this->model),
            'situation' => app(CommerceDocumentationService::class)->generateSituationPdf($this->model),
            'purchase_order' => app(CommerceDocumentationService::class)->generatePurchaseOrderPdf($this->model),
            default => throw new \InvalidArgumentException("Invalid namespace: {$this->namespace}"),
        };
    }
}
