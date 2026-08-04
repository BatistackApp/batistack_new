<?php

namespace App\Jobs\Commerce;

use App\Mail\Commerce\CustomerInvoiceMail;
use App\Models\Commerce\CustomerInvoice;
use App\Models\User;
use App\Notifications\Commerce\InvoiceSendingFailedNotification;
use App\Services\Commerce\CommerceDocumentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendCustomerInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 300];

    public function __construct(protected CustomerInvoice $invoice) {}

    public function handle(CommerceDocumentationService $documents): void
    {
        try {
            $this->invoice->loadMissing(['client.primaryContact']);

            if (! in_array($this->invoice->status->value, ['validated', 'partially_paid', 'paid'], true)) {
                Log::warning("Invoice {$this->invoice->reference} cannot be sent from status {$this->invoice->status->value}");

                return;
            }

            $email = $this->invoice->client?->getPrimaryContact()?->email ?: $this->invoice->client?->email;
            if (! $email) {
                Log::warning("No valid contact email for invoice {$this->invoice->reference}");

                return;
            }

            $pdfPath = $documents->generateInvoicePdf($this->invoice);

            Mail::to($email)->send(new CustomerInvoiceMail($this->invoice, $pdfPath));

            $this->invoice->updateQuietly([
                'sent_at' => now(),
            ]);

            Log::info("Invoice {$this->invoice->reference} sent to {$email}");
        } catch (\Throwable $e) {
            Log::error("Error sending invoice {$this->invoice->reference}: ".$e->getMessage());
            throw $e;
        }
    }

    public function failed(?\Throwable $exception = null): void
    {
        Log::critical("Invoice {$this->invoice->reference} email delivery failed permanently", [
            'error' => $exception?->getMessage(),
        ]);

        $admins = User::where('is_admin', true)->get();
        Notification::send(
            $admins,
            new InvoiceSendingFailedNotification($this->invoice, $exception ?? new \RuntimeException('Unknown mail delivery error'))
        );
    }
}
