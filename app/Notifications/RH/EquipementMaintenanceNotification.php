<?php

namespace App\Notifications\RH;

use App\Models\RH\Equipement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EquipementMaintenanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Equipement $equipement,
        private string $type = 'expired' // 'expired' ou 'maintenance'
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        if ($this->type === 'expired') {
            $subject = '⚠️ EPI EXPIRÉ - Action requise';
            $message = (new MailMessage)
                ->subject($subject)
                ->line("⚠️ L'équipement '{$this->equipement->label}' est EXPIRÉ")
                ->line("Employé: {$this->equipement->employee->getFullName()}")
                ->line("Date d'expiration dépassée: {$this->equipement->expires_at->format('d/m/Y')}")
                ->line('Cet équipement ne doit plus être utilisé.');
        } else {
            $subject = "Maintenance équipement - {$this->equipement->label}";
            $message = (new MailMessage)
                ->subject($subject)
                ->line("Maintenance requise pour '{$this->equipement->label}'")
                ->line("Employé: {$this->equipement->employee->getFullName()}")
                ->line('Dernière vérification: '.($this->equipement->last_check_at?->format('d/m/Y') ?? 'Jamais'))
                ->line('Une vérification annuelle est requise.');
        }

        return $message->action('Gérer équipements', url('/rh/equipements'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'equipement_id' => $this->equipement->id,
            'employee_id' => $this->equipement->employee_id,
            'label' => $this->equipement->label,
            'type' => $this->type,
            'expires_at' => $this->equipement->expires_at,
            'last_check_at' => $this->equipement->last_check_at,
        ];
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
