<?php

namespace App\Notifications\Articles;

use App\Filament\Commerce\Resources\PurchaseOrders\PurchaseOrderResource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForecastRuptureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $poCount, public int $urgentCount) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Prévisions de ruptures de stock')
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("La prévision prédictive a analysé l'historique (90j), la saisonnalité (24 mois) et les besoins BIM des chantiers planifiés.")
            ->line("{$this->poCount} commande(s) prédictive(s) générée(s), dont {$this->urgentCount} urgente(s) (rupture <=14j).")
            ->action('Voir les Commandes d\'Achat', PurchaseOrderResource::getUrl('index', panel: 'commerce'))
            ->line('Vérifiez les quantités et dates de commande suggérées dans le widget Prévisions.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Prévisions de ruptures',
            'body' => "{$this->poCount} commande(s) prédictive(s) générée(s) ({$this->urgentCount} urgente(s) <=14j).",
            'icon' => 'heroicon-o-chart-bar',
            'url' => PurchaseOrderResource::getUrl('index', panel: 'commerce'),
        ];
    }
}
