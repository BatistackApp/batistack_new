<?php

namespace App\Notifications\Articles;

use App\Models\Articles\StockMouvement;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public StockMouvement $mouvement, public float $remainingQuantity) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Alerte Péremption de Lot')
            ->line('Le lot '.$this->mouvement->batch_number.' pour l\'article '.$this->mouvement->stock->item->name.' arrive à expiration le '.$this->mouvement->expiration_date->format('d/m/Y').'.')
            ->line('Quantité restante estimée : '.number_format($this->remainingQuantity, 2))
            ->action('Voir le stock', url('/admin/stocks'))
            ->line('Merci de vérifier ce lot.');
    }

    public function toArray(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Lot bientôt expiré : '.$this->mouvement->stock->item->name)
            ->body('Lot : '.$this->mouvement->batch_number.' | Expire le : '.$this->mouvement->expiration_date->format('d/m/Y').' | Reste : '.number_format($this->remainingQuantity, 2))
            ->warning()
            ->getDatabaseMessage();
    }
}
