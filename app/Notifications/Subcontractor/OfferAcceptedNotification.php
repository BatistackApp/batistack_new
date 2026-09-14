<?php

namespace App\Notifications\Subcontractor;

use App\Models\Tiers\ConsultationOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferAcceptedNotification extends Notification implements ShouldQueue
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
            ->subject("Offre acceptée : {$consultation->title}")
            ->greeting('Votre offre a été retenue !')
            ->line("Félicitations ! Votre offre pour l'appel d'offres a été acceptée par l'entreprise principale.")
            ->line('**Consultation** : '.$consultation->title)
            ->line('**Chantier** : '.($consultation->chantier?->reference ?? '—'))
            ->line('**Montant de votre offre** : '.number_format($this->offer->amount, 2, ',', ' ').' € HT')
            ->action('Voir la consultation', url("/sous-traitant/consultations/{$consultation->id}"))
            ->line("L'équipe Batistack.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title("Offre acceptée : {$this->offer->consultation->title}")
            ->body('Montant : '.number_format($this->offer->amount, 2, ',', ' ').' € HT')
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
