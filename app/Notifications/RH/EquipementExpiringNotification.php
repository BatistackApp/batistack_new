<?php

namespace App\Notifications\RH;

use App\Models\RH\Equipement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EquipementExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Equipement $equipement, public bool $isExpired = false) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = $this->isExpired
            ? "⚠️ EPI PÉRIMÉ - {$this->equipement->label}"
            : "Rappel: EPI expire bientôt - {$this->equipement->label}";

        $message = (new MailMessage)
            ->subject($subject);

        if ($this->isExpired) {
            $message->line("⚠️ L'équipement '{$this->equipement->label}' est PÉRIMÉ!");
            $message->line("Employé: {$this->equipement->employee->getFullName()}");
            $message->line("Date d'expiration: {$this->equipement->expires_at->format('d/m/Y')}");
            $message->line("Marque: {$this->equipement->brand} - Modèle: {$this->equipement->model_name}");
        } else {
            $message->line("L'équipement '{$this->equipement->label}' expire dans 30 jours");
            $message->line("Employé: {$this->equipement->employee->getFullName()}");
            $message->line("Date d'expiration: {$this->equipement->expires_at->format('d/m/Y')}");
        }

        return $message->action('Voir équipements', url('/rh/equipements'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'equipement_id' => $this->equipement->id,
            'employee_id' => $this->equipement->employee_id,
            'label' => $this->equipement->label,
            'is_expired' => $this->isExpired,
            'expires_at' => $this->equipement->expires_at,
        ];
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
