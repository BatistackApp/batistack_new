<?php

namespace App\Notifications\Customer;

use App\Models\Commerce\CustomerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaiementRecuNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected CustomerInvoice $invoice) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Paiement reçu — Facture {$this->invoice->reference}")
            ->greeting('Paiement enregistré')
            ->line("Nous avons bien reçu le paiement de la facture n°{$this->invoice->reference}.")
            ->line('**Montant payé** : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' € TTC')
            ->line('**Date** : '.$this->getPaymentDate())
            ->action('Voir la facture', url("/customer/customer-invoices/{$this->invoice->id}"))
            ->line('Merci pour votre règlement.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title("Paiement reçu — {$this->invoice->reference}")
            ->body('Montant : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' € TTC')
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }

    private function getPaymentDate(): string
    {
        $latestAllocation = $this->invoice->allocations()
            ->with('payment')
            ->latest()
            ->first();

        if ($latestAllocation?->payment?->payment_date) {
            return $latestAllocation->payment->payment_date->format('d/m/Y');
        }

        return now()->format('d/m/Y');
    }
}
