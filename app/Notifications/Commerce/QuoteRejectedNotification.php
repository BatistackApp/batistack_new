<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\CustomerQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteRejectedNotification extends Notification implements ShouldQueue
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
            ->error()
            ->subject("Devis rejeté : {$this->quote->reference}")
            ->greeting('Alerte Commerciale')
            ->line("Le devis n°{$this->quote->reference} a été rejeté par {$this->quote->client->name}.")
            ->line('Montant : '.number_format($this->quote->total_ttc, 2, ',', ' ').' € TTC')
            ->action('Voir le devis', url("/commerce/quotes/{$this->quote->id}"))
            ->line('Prenez contact avec le client pour connaître les raisons du refus et proposer une alternative.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->danger()
            ->title("Devis rejeté : {$this->quote->reference}")
            ->body($this->quote->client->name)
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
