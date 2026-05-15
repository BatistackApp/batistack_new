<?php

namespace App\Notifications\Chantiers;

use App\Models\Chantiers\Chantier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Chantier $chantier) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("🕒 Retard de livraison : {$this->chantier->name}")
            ->line("Le chantier **{$this->chantier->name}** est toujours actif alors que sa date de fin prévisionnelle était fixée au **{$this->chantier->end_date_preview->format('d/m/Y')}**.")
            ->action('Mettre à jour le planning', url("/chantiers/chantiers/{$this->chantier->id}/edit"))
            ->line('Veuillez régulariser le statut ou prolonger le planning prévisionnel.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->danger()
            ->icon($this->toArray($notifiable)['icon'])
            ->title($this->toArray($notifiable)['title'])
            ->body($this->toArray($notifiable)['message'])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Chantier en retard',
            'message' => "La date de fin prévue est dépassée pour {$this->chantier->reference}.",
            'icon' => Phosphor::HourglassLow,
            'color' => 'danger',
        ];
    }
}
