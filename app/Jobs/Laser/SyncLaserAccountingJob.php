<?php

namespace App\Jobs\Laser;

use App\Models\Accounting\AccountingSync;
use App\Models\Laser\LaserCreditNote;
use App\Models\Laser\LaserInvoice;
use App\Notifications\Accounting\LaserAccountingSyncFailedNotification;
use App\Services\Laser\LaserInvoiceAccountingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Throwable;

class SyncLaserAccountingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $backoff = 60;

    public function __construct(
        public string $documentType,
        public int $documentId,
    ) {}

    public function handle(LaserInvoiceAccountingService $service): void
    {
        $document = $this->document();
        $sync = AccountingSync::query()->firstOrCreate(
            [
                'syncable_type' => $document->getMorphClass(),
                'syncable_id' => $document->getKey(),
            ],
            ['status' => 'pending']
        );
        $sync->increment('attempts');

        if ($this->documentType === 'credit_note') {
            $service->syncCreditNote($document);
        } else {
            $service->syncInvoice($document);
        }
    }

    public function failed(Throwable $exception): void
    {
        $document = $this->documentType === 'credit_note'
            ? LaserCreditNote::query()->find($this->documentId)
            : LaserInvoice::query()->find($this->documentId);

        if (! $document) {
            report($exception);
            return;
        }

        $sync = AccountingSync::query()->firstOrCreate(
            [
                'syncable_type' => $document->getMorphClass(),
                'syncable_id' => $document->getKey(),
            ],
            ['status' => 'pending']
        );
        $sync->update([
            'status' => 'failed',
            'last_error' => $exception->getMessage(),
        ]);

        User::query()->each(fn (User $user) => $user->notify(new LaserAccountingSyncFailedNotification($sync, $exception)));
    }

    private function document(): LaserInvoice|LaserCreditNote
    {
        return $this->documentType === 'credit_note'
            ? LaserCreditNote::query()->findOrFail($this->documentId)
            : LaserInvoice::query()->findOrFail($this->documentId);
    }
}
