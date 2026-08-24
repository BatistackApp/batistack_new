<?php

namespace App\Notifications\Subcontractor;

use App\Models\Tiers\Consultation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConsultationPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Consultation $consultation,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvel appel d'offres : {$this->consultation->title}")
            ->greeting('Nouvelle consultation disponible')
            ->line("Un nouvel appel d'offres a été publié et pourrait vous intéresser :")
            ->line('**Titre** : '.$this->consultation->title)
            ->line('**Chantier** : '.($this->consultation->chantier?->reference ?? '—'))
            ->line('**Date limite** : '.$this->consultation->deadline->format('d/m/Y H:i'))
            ->action('Voir la consultation', url("/sous-traitant/consultations/{$this->consultation->id}"))
            ->line("L'équipe Batistack.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title("Consultation : {$this->consultation->title}")
            ->body('Date limite : '.$this->consultation->deadline->format('d/m/Y H:i'))
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
