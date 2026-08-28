<?php

namespace App\Notifications\Subcontractor;

use App\Models\Chantiers\Chantier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChantierAllocationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Chantier $chantier,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouveau chantier assigné : {$this->chantier->reference}")
            ->greeting('Vous avez été assigné à un nouveau chantier')
            ->line('Vous avez été rattaché au chantier suivant :')
            ->line('**Référence** : '.$this->chantier->reference)
            ->line('**Nom** : '.$this->chantier->name)
            ->line('**Adresse** : '.($this->chantier->address ?? 'Non renseignée'))
            ->line('**Statut** : '.($this->chantier->status?->getLabel() ?? 'Non défini'))
            ->action('Voir le chantier', url("/sous-traitant/chantiers/{$this->chantier->id}"))
            ->line("L'équipe Batistack.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title("Nouveau chantier : {$this->chantier->reference}")
            ->body($this->chantier->name)
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
