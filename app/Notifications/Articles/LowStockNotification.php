<?php

namespace App\Notifications\Articles;

use App\Models\Articles\Stock;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class LowStockNotification extends Notification
{
    public function __construct(public Stock $stock) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Alerte de Stock : {$this->stock->item->name}")
            ->greeting('Seuil critique atteint')
            ->line("L'article {$this->stock->item->name} (Réf: {$this->stock->item->reference}) est en rupture ou sous le seuil de sécurité.")
            ->line("Dépôt : {$this->stock->warehouse->name}")
            ->line("Quantité actuelle : {$this->stock->quantity}")
            ->line("Seuil d'alerte : {$this->stock->min_threshold}")
            ->action('Voir l\'inventaire', url('/admin/stocks'))
            ->error();
    }

    public function toDatabase($notifiable): array
    {
        $notification = \Filament\Notifications\Notification::make()
            ->danger()
            ->title('Stock critique : '.$this->stock->item->name)
            ->body("Quantité restante : {$this->stock->quantity} dans {$this->stock->warehouse->name}.")
            ->icon(Phosphor::Warning);

        if ($this->stock->item->supplier_id) {
            $notification->actions([
                \Filament\Notifications\Actions\Action::make('request_quote')
                    ->label('Demander prix')
                    ->button()
                    ->url(route('articles.request-quote', ['item' => $this->stock->item_id]))
                    ->markAsRead(),
            ]);
        }

        return $notification->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Stock critique : '.$this->stock->item->name,
            'body' => "Quantité restante : {$this->stock->quantity} dans {$this->stock->warehouse->name}.",
            'icon' => Phosphor::Warning,
            'color' => 'danger',
            'action_url' => "/admin/items/{$this->stock->item_id}/edit",
        ];
    }
}
