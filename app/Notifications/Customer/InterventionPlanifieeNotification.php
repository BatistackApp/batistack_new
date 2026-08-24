<?php

namespace App\Notifications\Customer;

use App\Models\Interventions\Intervention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterventionPlanifieeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Intervention $intervention) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $chantierRef = $this->intervention->chantier?->reference ?? 'N/A';
        $typeLabel = $this->intervention->type?->getLabel() ?? 'Intervention';

        return (new MailMessage)
            ->subject("Intervention planifiée : {$this->intervention->reference}")
            ->greeting('Nouvelle intervention planifiée')
            ->line('Une intervention a été planifiée pour votre compte.')
            ->line("**Référence** : {$this->intervention->reference}")
            ->line("**Type** : {$typeLabel}")
            ->line("**Chantier** : {$chantierRef}")
            ->line('**Date prévue** : '.$this->intervention->scheduled_at?->format('d/m/Y à H:i'))
            ->line('**Description** : '.$this->intervention->description)
            ->action('Voir les détails', url("/customer/interventions/{$this->intervention->id}"))
            ->line("L'équipe Batistack.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title("Intervention planifiée : {$this->intervention->reference}")
            ->body('Prévue le '.$this->intervention->scheduled_at?->format('d/m/Y à H:i'))
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
