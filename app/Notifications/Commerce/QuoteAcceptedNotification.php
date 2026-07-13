<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\CustomerQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteAcceptedNotification extends Notification implements ShouldQueue
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
            ->success()
            ->subject("✓ Devis accepté : {$this->quote->reference}")
            ->greeting('Excellente nouvelle !')
            ->line("Le devis n°{$this->quote->reference} a été accepté par {$this->quote->client->name}.")
            ->line('**Montant du marché** : '.number_format($this->quote->total_ttc, 2, ',', ' ').' € TTC')
            ->line("**Date d'acceptation** : {$this->quote->signed_at->format('d/m/Y H:i')}")
            ->action('Voir la commande créée', url('/commerce/orders/'.($this->quote->order?->id ?? '')))
            ->line('Une commande client a été générée automatiquement. Vous pouvez maintenant lancer la préparation de la livraison.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title("Devis accepté : {$this->quote->reference}")
            ->body('Montant : '.number_format($this->quote->total_ttc, 2, ',', ' ').' € TTC')
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
