<?php

namespace App\Notifications\Chantiers;

use App\Models\Chantiers\ChantierLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierIncidentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected ChantierLog $log) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("🚨 INCIDENT SIGNALÉ : {$this->log->chantier->name}")
            ->greeting('Alerte Incident Terrain')
            ->line("Un incident a été consigné dans le journal de bord du chantier **{$this->log->chantier->reference}** par {$this->log->user->name}.")
            ->line("Date de l'événement : {$this->log->date->format('d/m/Y')}")
            ->line('Description :')
            ->line("_{$this->log->content}_")
            ->action('Voir le journal de chantier', url("/chantiers/chantiers/{$this->log->chantier_id}"))
            ->line("Merci d'intervenir ou de prendre contact avec le chef de chantier si nécessaire.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->danger()
            ->title('Incident signalé')
            ->body("Un incident a été rapporté sur le chantier {$this->log->chantier->reference}.")
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Incident signalé',
            'message' => "Un incident a été rapporté sur le chantier {$this->log->chantier->reference}.",
            'icon' => Phosphor::WarningOctagon,
            'color' => 'danger',
            'action_url' => "/chantiers/chantiers/{$this->log->chantier_id}",
        ];
    }
}
