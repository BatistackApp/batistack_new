<?php

namespace App\Notifications\Articles;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestockOrdersGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $poCount) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Brouillons de Commandes d'Achat générés")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("L'analyse nocturne des stocks a détecté des articles sous leur seuil d'alerte.")
            ->line("Le système a généré automatiquement **{$this->poCount} commande(s) d'achat (Brouillon)** ciblée(s) par fournisseur.")
            ->action("Voir les Commandes d'Achat", url('/commerce/purchase-orders'))
            ->line('Veuillez les vérifier et les confirmer pour déclencher les approvisionnements.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Brouillons de Commandes d'Achat",
            'body' => "{$this->poCount} commande(s) d'achat générée(s) automatiquement suite aux ruptures de stock.",
            'icon' => 'heroicon-o-shopping-cart',
            'url' => url('/commerce/purchase-orders'),
        ];
    }
}
