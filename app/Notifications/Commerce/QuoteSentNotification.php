<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\CustomerQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteSentNotification extends Notification implements ShouldQueue
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
            ->subject("Devis n°{$this->quote->reference} - {$this->quote->client->name}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Nous vous transmettons ci-joint notre devis pour votre projet.')
            ->line("**Numéro de devis** : {$this->quote->reference}")
            ->line('**Montant HT** : '.number_format($this->quote->total_ht, 2, ',', ' ').' €')
            ->line('**Montant TTC** : '.number_format($this->quote->total_ttc, 2, ',', ' ').' €')
            ->line("**Date d'expiration** : {$this->quote->expires_at->format('d/m/Y')}")
            ->action('Consulter le devis', url("/commerce/quotes/{$this->quote->id}"))
            ->line("Ce devis est valable jusqu'au {$this->quote->expires_at->format('d/m/Y')}. N'hésitez pas à nous contacter pour toute question.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->color('primary')
            ->title("Devis {$this->quote->reference} envoyé")
            ->body('Montant : '.number_format($this->quote->total_ttc, 2, ',', ' ').' € TTC')
            ->getDatabaseMessage();
    }
}
