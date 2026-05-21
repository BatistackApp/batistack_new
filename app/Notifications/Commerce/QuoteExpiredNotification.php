<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\CustomerQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected CustomerQuote $quote) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Devis expiré : {$this->quote->reference}")
            ->greeting('Alerte Commerciale')
            ->line("Le devis n°{$this->quote->reference} pour {$this->quote->client->name} a expiré.")
            ->line("**Date d'expiration** : {$this->quote->expires_at->format('d/m/Y')}")
            ->line('**Montant** : '.number_format($this->quote->total_ttc, 2, ',', ' ').' € TTC')
            ->action('Relancer le client', url("/commerce/quotes/{$this->quote->id}"))
            ->line('Veuillez recontacter le client pour relancer votre proposition ou créer une nouvelle offre.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->warning()
            ->title("Devis expiré : {$this->quote->reference}")
            ->body($this->quote->client->name.' - À relancer')
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
