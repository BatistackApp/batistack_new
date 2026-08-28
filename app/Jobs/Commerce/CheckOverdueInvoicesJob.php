<?php

namespace App\Jobs\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Notifications\Commerce\PaymentReminderNotification;
use App\Notifications\Customer\RelanceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class CheckOverdueInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Starting overdue invoice check job...');

        // 1. Récupération des factures validées non payées avec échéance dépassée
        $overdueInvoices = CustomerInvoice::where('status', InvoiceStatus::VALIDATED)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->with(['client.primaryContact', 'order'])
            ->get();

        if ($overdueInvoices->isEmpty()) {
            Log::info('No overdue invoices detected');

            return;
        }

        Log::info("Found {$overdueInvoices->count()} overdue invoices");

        // 2. Tri par niveau de retard et envoi des relances adaptées
        foreach ($overdueInvoices as $invoice) {
            $daysLate = now()->diffInDays($invoice->due_date);
            $reminderLevel = $this->determineReminderLevel($daysLate);

            // Vérification : pas de relance si elle a déjà été envoyée récemment
            if ($this->hasRecentReminder($invoice, $reminderLevel)) {
                continue;
            }

            $this->sendReminder($invoice, $reminderLevel, $daysLate);
        }

        Log::info('Overdue invoice check job completed');
    }

    /**
     * Détermine le niveau de relance en fonction du nombre de jours de retard.
     *
     * @return int (1, 2 ou 3)
     */
    protected function determineReminderLevel(int $daysLate): int
    {
        return match (true) {
            $daysLate <= 30 => 1,   // Relance amiable (0-30 jours)
            $daysLate <= 60 => 2,   // Mise en demeure (31-60 jours)
            default => 3,           // Dernière relance (60+ jours)
        };
    }

    /**
     * Vérifie si une relance de ce niveau a déjà été envoyée récemment.
     */
    protected function hasRecentReminder(CustomerInvoice $invoice, int $level): bool
    {
        return $invoice->dunning_level === $level
            && $invoice->last_dunning_at !== null
            && $invoice->last_dunning_at->isAfter(now()->subDays(7));
    }

    /**
     * Envoie la relance au client.
     */
    protected function sendReminder(CustomerInvoice $invoice, int $level, int $daysLate): void
    {
        // 1. Récupération du contact principal
        $contact = $invoice->client?->primaryContact;
        if (! $contact || ! $contact->email) {
            Log::warning("No valid contact for invoice {$invoice->reference}");

            return;
        }

        // 2. Envoi de la notification
        try {
            $contact->notify(
                new PaymentReminderNotification($invoice, $level, $daysLate)
            );
            $contact->notify(
                new RelanceNotification($invoice, $level, $daysLate)
            );

            // 3. Logging du rappel
            Log::info("Reminder level {$level} sent for invoice {$invoice->reference} ({$daysLate} days late)");

            // 4. Mise à jour du niveau de relance sur la facture
            $invoice->update([
                'dunning_level' => $level,
                'last_dunning_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send reminder for invoice {$invoice->reference}: ".$e->getMessage());
        }
    }
}
