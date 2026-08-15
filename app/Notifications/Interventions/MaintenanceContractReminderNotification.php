<?php

namespace App\Notifications\Interventions;

use App\Models\Interventions\MaintenanceContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceContractReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected MaintenanceContract $contract) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $equipment = $this->contract->clientEquipment;
        $dueDate = $this->contract->next_due_date?->format('d/m/Y') ?? 'à définir';

        return (new MailMessage)
            ->subject("Entretien préventif à venir - {$equipment?->name}")
            ->greeting('Bonjour,')
            ->line("Dans le cadre de votre **contrat d'entretien** **{$this->contract->name}** (réf. {$this->contract->reference}), une intervention de maintenance préventive est planifiée.")
            ->line('- **Équipement :** '.($equipment?->name ?? 'N/A').($equipment?->brand ? " ({$equipment->brand})" : ''))
            ->line("- **Échéance :** {$dueDate}")
            ->line('- **Fréquence :** '.$this->contract->frequency->getLabel())
            ->line('Notre équipe vous contactera pour confirmer le créneau. Merci de nous faciliter l\'accès à votre équipement.')
            ->action('Voir mon espace client', url('/customer'));
    }
}
