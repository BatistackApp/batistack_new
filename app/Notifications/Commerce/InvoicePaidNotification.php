<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\CustomerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaidNotification extends Notification implements ShouldQueue
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
            ->success()
            ->subject("✓ Facture payée : {$this->invoice->reference}")
            ->greeting('Paiement enregistré')
            ->line("Nous vous confirmons que le paiement de la facture n°{$this->invoice->reference} a été reçu.")
            ->line('**Montant payé** : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' € TTC')
            ->line('**Date de paiement** : '.now()->format('d/m/Y'))
            ->action('Télécharger la quittance', url("/commerce/invoices/{$this->invoice->id}/receipt"))
            ->line("Merci pour cette transaction. N'hésitez pas à nous recontacter pour vos futurs projets.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title("Facture payée : {$this->invoice->reference}")
            ->body('Montant : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' € TTC')
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
