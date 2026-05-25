<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\CustomerDeliveryNote;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CustomerDeliveryNote $deliveryNote) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $orderReference = $this->deliveryNote->order ? $this->deliveryNote->order->reference : 'N/A';
        $clientName = $this->deliveryNote->client->primaryContact->full_name;

        return (new MailMessage)
            ->subject('Votre commande a été expédiée !')
            ->greeting("Bonjour {$clientName},")
            ->line("Votre note de livraison **#{$this->deliveryNote->reference}** a été expédiée.")
            ->line("Celle-ci correspond à la commande **#{$orderReference}**.")
            ->action('Voir la note de livraison', url('/customer/customer-delivery-notes/'.$this->deliveryNote->id)) // Assuming a route to view delivery notes
            ->line('Merci de votre confiance !')
            ->salutation('Cordialement,');
    }

    public function toDatabase($notifiable): array
    {
        $orderReference = $this->deliveryNote->order ? $this->deliveryNote->order->reference : 'N/A';

        return \Filament\Notifications\Notification::make()
            ->success()
            ->title('Votre commande a été expédiée !')
            ->body("Votre note de livraison **#{$this->deliveryNote->reference}** a été expédiée. Celle-ci correspond à la commande **#{$orderReference}**.")
            ->actions([
                Action::make('view')
                    ->label('Suivre la livraison')
                    ->icon(Phosphor::MapPin)
                    ->url("/customer/customer-delivery-notes/{$this->deliveryNote->id}"),
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
