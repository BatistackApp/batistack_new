<?php

namespace App\Notifications\Subcontractor;

use App\Models\Tiers\ConsultationOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ConsultationOffer $offer,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $consultation = $this->offer->consultation;

        return (new MailMessage)
            ->subject("Offre non retenue : {$consultation->title}")
            ->greeting('Votre offre n\'a pas été retenue')
            ->line("Nous vous informons que votre offre pour l'appel d'offres n'a pas été retenue cette fois-ci.")
            ->line('**Consultation** : '.$consultation->title)
            ->line('**Chantier** : '.($consultation->chantier?->reference ?? '—'))
            ->line("Nous vous encourageons à répondre aux prochains appels d'offres.")
            ->action('Voir la consultation', url("/sous-traitant/consultations/{$consultation->id}"))
            ->line("L'équipe Batistack.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title("Offre non retenue : {$this->offer->consultation->title}")
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
