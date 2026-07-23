<?php

namespace App\Notifications\Gpao;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use Filament\Notifications\Notification as FilamentNotification;

class ProductionAlertNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $url;

    public function __construct($title, $message, $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray($notifiable)
    {
        // For Filament database notifications
        return FilamentNotification::make()
            ->title($this->title)
            ->body($this->message)
            ->danger()
            ->getDatabaseMessage();
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon('/apple-touch-icon.png')
            ->body($this->message)
            ->action('Voir l\'alerte', $this->url ?: '/gpao/manufacturing-orders')
            ->data(['url' => $this->url ?: '/gpao/manufacturing-orders']);
    }
}
