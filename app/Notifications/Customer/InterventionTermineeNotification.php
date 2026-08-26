<?php

namespace App\Notifications\Customer;

use App\Models\Interventions\Intervention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterventionTermineeNotification extends Notification implements ShouldQueue
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
            ->subject("Intervention terminée : {$this->intervention->reference}")
            ->greeting('Intervention terminée')
            ->line("L'intervention suivante a été réalisée avec succès.")
            ->line("**Référence** : {$this->intervention->reference}")
            ->line("**Type** : {$typeLabel}")
            ->line("**Chantier** : {$chantierRef}")
            ->line('**Date de réalisation** : '.$this->intervention->completed_at?->format('d/m/Y à H:i'))
            ->action('Voir le rapport', url("/customer/interventions/{$this->intervention->id}"))
            ->line("N'hésitez pas à nous contacter pour toute question.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title("Intervention terminée : {$this->intervention->reference}")
            ->body('Réalisée le '.$this->intervention->completed_at?->format('d/m/Y à H:i'))
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
