<?php

namespace App\Notifications\Customer;

use App\Models\Commerce\CustomerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FactureRecueNotification extends Notification implements ShouldQueue
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
            ->subject("Nouvelle facture n°{$this->invoice->reference}")
            ->greeting("Facture {$typeLabel} reçue")
            ->line('Une nouvelle facture a été émise à votre attention.')
            ->line("**Numéro** : {$this->invoice->reference}")
            ->line("**Type** : {$typeLabel}")
            ->line('**Montant TTC** : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' €')
            ->line("**Échéance** : {$this->invoice->due_date->format('d/m/Y')}")
            ->action('Consulter la facture', url("/customer/customer-invoices/{$this->invoice->id}"))
            ->line('Merci pour votre confiance.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title("Facture {$this->invoice->reference} reçue")
            ->body('Montant TTC : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' € — Échéance : '.$this->invoice->due_date?->format('d/m/Y'))
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
