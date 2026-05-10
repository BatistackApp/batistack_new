<?php

namespace App\Notifications\Core;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfigurationChangedNotification extends Notification
{
    public function __construct(public string $settingKey, public string $userName) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Batistack : Modification de configuration')
            ->line("Le paramètre '{$this->settingKey}' a été modifié par {$this->userName}.")
            ->action('Voir les paramètres', url('/core/settings'))
            ->line('Cette action a entraîné une invalidation automatique du cache système.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->danger()
            ->title('Modification de configuration')
            ->body("Le paramètre '{$this->settingKey}' a été modifié par {$this->userName}.")
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'key' => $this->settingKey,
            'user' => $this->userName,
            'message' => 'Mise à jour du paramètre système.',
        ];
    }
}
