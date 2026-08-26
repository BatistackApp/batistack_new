<?php

namespace App\Notifications\RH;

use App\Models\RH\Qualification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class QualificationExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Qualification $qualification,
        protected bool $isExpired = false
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusText = $this->isExpired ? 'est expirée depuis le' : 'arrive à expiration le';
        $subject = ($this->isExpired ? '🔴 ALERTE : ' : '⚠️ RAPPEL : ')."Habilitation {$this->qualification->label->getDescription()}";

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Gestion des compétences')
            ->line("L'habilitation suivante pour l'employé **{$this->qualification->employee->full_name}** {$statusText} **{$this->qualification->expires_at->format('d/m/Y')}**.")
            ->line("Type : {$this->qualification->type->getLabel()}")
            ->line("Libellé : {$this->qualification->label->value}")
            ->action('Gérer le dossier employé', url("/admin/employees/{$this->qualification->employee_id}/edit"))
            ->error($this->isExpired);
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->status(function () {
                return $this->isExpired ? 'danger' : 'warning';
            })
            ->title(function () {
                return $this->isExpired ? 'Expiration : ' : 'Renouvellement : '.$this->qualification->label->getDescription();
            })
            ->body("Employé : {$this->qualification->employee->full_name}. Échéance : {$this->qualification->expires_at->format('d/m/Y')}.")
            ->icon($this->isExpired ? Phosphor::Prohibit : Phosphor::Warning)
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => ($this->isExpired ? 'Expiration : ' : 'Renouvellement : ').$this->qualification->label->value,
            'body' => "Employé : {$this->qualification->employee->full_name}. Échéance : {$this->qualification->expires_at->format('d/m/Y')}.",
            'icon' => $this->isExpired ? Phosphor::Prohibit : Phosphor::Warning,
            'color' => $this->isExpired ? 'danger' : 'warning',
        ];
    }
}
