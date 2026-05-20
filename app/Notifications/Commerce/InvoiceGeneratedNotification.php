<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\CustomerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected CustomerInvoice $invoice) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $typeLabel = $this->invoice->type->getLabel();

        return (new MailMessage)
            ->subject("Facture n°{$this->invoice->reference}")
            ->greeting("Facture {$typeLabel}")
            ->line('Nous vous adressons la facture relative à vos derniers achats.')
            ->line("**Numéro de facture** : {$this->invoice->reference}")
            ->line("**Type** : {$typeLabel}")
            ->line('**Montant TTC** : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' €')
            ->line("**Échéance de paiement** : {$this->invoice->due_date->format('d/m/Y')}")
            ->action('Télécharger la facture', url("/commerce/invoices/{$this->invoice->id}/pdf"))
            ->line("Veuillez effectuer le paiement avant la date d'échéance indiquée. Merci pour votre confiance.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title("Facture {$this->invoice->reference} émise")
            ->body('Montant TTC : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' €')
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
