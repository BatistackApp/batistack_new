<?php

namespace App\Notifications\Chantiers;

use App\Models\Chantiers\WeatherAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeatherAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WeatherAlert $alert) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Alerte Météo sur Chantier: '.$this->alert->chantier->name)
            ->line("Une alerte météo de type {$this->alert->type} (vigilance {$this->alert->severity}) a été détectée sur votre chantier.")
            ->line($this->alert->description)
            ->action('Voir le Chantier', url('/chantiers/'.$this->alert->chantier_id))
            ->line('Merci de prendre les précautions nécessaires.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'weather_alert_id' => $this->alert->id,
            'chantier_id' => $this->alert->chantier_id,
            'message' => "Alerte {$this->alert->type} sur {$this->alert->chantier->name}",
        ];
    }
}
