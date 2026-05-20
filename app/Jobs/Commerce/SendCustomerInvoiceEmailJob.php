<?php

namespace App\Jobs\Commerce;

use App\Models\Commerce\CustomerInvoice;
use App\Models\User;
use App\Notifications\Commerce\InvoiceSendingFailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Log;

class SendCustomerInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;              // Nombre de tentatives

    public int $timeout = 120;          // Timeout en secondes

    public array $backoff = [60, 300];    // Délais de retry (1min, 5min)

    public function __construct(protected CustomerInvoice $invoice) {}

    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        try {
            // 1. Vérification que la facture est en statut VALIDATED
            if ($this->invoice->status->value !== 'validated') {
                Log::warning("Invoice {$this->invoice->reference} is not in validated status");

                return;
            }

            // 2. Récupération du contact principal du client
            $contact = $this->invoice->client?->primaryContact;
            if (! $contact || ! $contact->email) {
                Log::warning("No valid contact email for invoice {$this->invoice->reference}");

                return;
            }

            // 3. Envoi de l'email avec la facture en pièce jointe
            $contact->notify(new InvoiceEmailNotification($this->invoice));

            // 4. Marquage du timestamp d'envoi
            $this->invoice->updateQuietly([
                'sent_at' => now(),
            ]);

            Log::info("Invoice {$this->invoice->reference} sent to {$contact->email}");

        } catch (\Exception $e) {
            Log::error("Error sending invoice {$this->invoice->reference}: ".$e->getMessage());
            throw $e; // Déclenche une retry
        }
    }

    public function fail(?\Throwable $exception = null): void
    {
        Log::critical("Invoice {$this->invoice->reference} email delivery failed permanently", [
            'error' => $exception->getMessage(),
        ]);

        // Notification au service Compta
        $admins = User::where('is_admin', true)->get();
        Notification::send(
            $admins,
            new InvoiceSendingFailedNotification($this->invoice, $exception)
        );
    }
}
