<?php

namespace App\Jobs\Laser;

use App\Models\Laser\LaserCreditNote;
use App\Models\Laser\LaserDeliveryNote;
use App\Models\Laser\LaserInvoice;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserQuote;
use App\Services\Laser\LaserDocumentationService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateLaserDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $namespace,
        public Model $model,
        public ?CarbonImmutable $expectedUpdatedAt = null,
    ) {
        if ($this->expectedUpdatedAt === null && $this->model->exists) {
            $this->expectedUpdatedAt = CarbonImmutable::instance($this->model->updated_at);
        }
    }

    public function handle(): void
    {
        match ($this->namespace) {
            'laser_quote' => $this->handleQuote(),
            'laser_order' => $this->handleOrder(),
            'laser_delivery_note' => $this->handleDeliveryNote(),
            'laser_invoice' => $this->handleInvoice(),
            'laser_credit_note' => $this->handleCreditNote(),
            default => throw new \InvalidArgumentException("Invalid namespace: {$this->namespace}"),
        };
    }

    private function handleQuote(): void
    {
        /** @var LaserQuote $freshQuote */
        $freshQuote = LaserQuote::with(['client', 'lines.material'])
            ->findOrFail($this->model->getKey());

        if ($this->expectedUpdatedAt && $freshQuote->updated_at->greaterThan($this->expectedUpdatedAt)) {
            self::dispatch($this->namespace, $freshQuote, CarbonImmutable::instance($freshQuote->updated_at));

            return;
        }

        app(LaserDocumentationService::class)->generateQuotePdf($freshQuote);
    }

    private function handleOrder(): void
    {
        /** @var LaserOrder $freshOrder */
        $freshOrder = LaserOrder::with(['client', 'lines.material', 'quote'])
            ->findOrFail($this->model->getKey());

        if ($this->expectedUpdatedAt && $freshOrder->updated_at->greaterThan($this->expectedUpdatedAt)) {
            self::dispatch($this->namespace, $freshOrder, CarbonImmutable::instance($freshOrder->updated_at));

            return;
        }

        app(LaserDocumentationService::class)->generateOrderPdf($freshOrder);
    }

    private function handleDeliveryNote(): void
    {
        /** @var LaserDeliveryNote $freshDelivery */
        $freshDelivery = LaserDeliveryNote::with(['client', 'lines.material', 'order'])
            ->findOrFail($this->model->getKey());

        if ($this->expectedUpdatedAt && $freshDelivery->updated_at->greaterThan($this->expectedUpdatedAt)) {
            self::dispatch($this->namespace, $freshDelivery, CarbonImmutable::instance($freshDelivery->updated_at));

            return;
        }

        app(LaserDocumentationService::class)->generateDeliveryNotePdf($freshDelivery);
    }

    private function handleInvoice(): void
    {
        /** @var LaserInvoice $freshInvoice */
        $freshInvoice = LaserInvoice::with(['client', 'lines.material', 'order'])
            ->findOrFail($this->model->getKey());

        if ($this->expectedUpdatedAt && $freshInvoice->updated_at->greaterThan($this->expectedUpdatedAt)) {
            self::dispatch($this->namespace, $freshInvoice, CarbonImmutable::instance($freshInvoice->updated_at));

            return;
        }

        app(LaserDocumentationService::class)->generateInvoicePdf($freshInvoice);
    }

    private function handleCreditNote(): void
    {
        /** @var LaserCreditNote $freshCreditNote */
        $freshCreditNote = LaserCreditNote::with(['client', 'invoice'])
            ->findOrFail($this->model->getKey());

        if ($this->expectedUpdatedAt && $freshCreditNote->updated_at->greaterThan($this->expectedUpdatedAt)) {
            self::dispatch($this->namespace, $freshCreditNote, CarbonImmutable::instance($freshCreditNote->updated_at));

            return;
        }

        app(LaserDocumentationService::class)->generateCreditNotePdf($freshCreditNote);
    }
}
