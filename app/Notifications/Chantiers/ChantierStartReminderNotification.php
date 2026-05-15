<?php

namespace App\Notifications\Chantiers;

use App\Models\Chantiers\Chantier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierStartReminderNotification extends Notification implements ShouldQueue
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
            ->subject("📅 Démarrage demain : {$this->chantier->name}")
            ->line("Rappel : Le chantier **{$this->chantier->name}** doit démarrer demain, le **{$this->chantier->start_date_preview->format('d/m/Y')}**.")
            ->line('Veuillez vous assurer que les équipes et le matériel sont mobilisés.')
            ->action('Dossier de lancement', url("/chantiers/chantiers/{$this->chantier->id}"));
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
            'title' => 'Démarrage demain',
            'message' => "Ouverture prévue pour {$this->chantier->name}.",
            'icon' => Phosphor::CalendarCheck,
            'color' => 'success',
        ];
    }
}
