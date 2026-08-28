<?php

namespace App\Jobs\Gpao;

use App\Models\Gpao\ManufacturingOrder;
use App\Services\Core\DocumentService;
use App\Services\Gpao\GpaoDocumentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateManufacturingOrderPdfJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $manufacturingOrderId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GpaoDocumentService $documentService): void
    {
        try {
            $order = ManufacturingOrder::findOrFail($this->manufacturingOrderId);

            // Delete existing PDFs to avoid duplicates
            $order->clearMediaCollection('pdf_documents');

            $pdfPath = $documentService->generateManufacturingOrderPdf($order);
            $disk = DocumentService::getDisk();

            $order->addMediaFromDisk($pdfPath, $disk)
                ->toMediaCollection('pdf_documents');

            Log::info('OF PDF generated and attached', ['order_id' => $order->id, 'reference' => $order->reference]);

        } catch (\Exception $e) {
            Log::error('Failed to generate OF PDF', [
                'order_id' => $this->manufacturingOrderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
