<?php

namespace App\Notifications\RH;

use App\Models\RH\Contract;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TrialPeriodEndingNotification extends Notification
{
    public function __construct(protected Contract $contract) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $employeeName = $this->contract->employee->full_name;
        $endDate = $this->contract->trial_end_date->format('d/m/Y');

        return (new MailMessage)
            ->subject("⚠️ Fin de période d'essai : {$employeeName}")
            ->greeting("Alerte RH - Période d'essai")
            ->line("La période d'essai de l'employé **{$employeeName}** arrive à échéance le **{$endDate}**.")
            ->line("Contrat : {$this->contract->job_title} ({$this->contract->type->getLabel()})")
            ->action('Consulter le dossier', url("/admin/employees/{$this->contract->employee_id}/edit"))
            ->line('Veuillez prendre les dispositions nécessaires pour le renouvellement ou la fin de collaboration avant cette date.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->warning()
            ->title("Fin de période d'essai : ".$this->contract->employee->full_name)
            ->body('Échéance le '.$this->contract->trial_end_date->format('d/m/Y').' pour le poste de '.$this->contract->job_title.'.')
            ->icon(Phosphor::ClockClockwise)
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "Fin de période d'essai : ".$this->contract->employee->full_name,
            'body' => 'Échéance le '.$this->contract->trial_end_date->format('d/m/Y').' pour le poste de '.$this->contract->job_title.'.',
            'icon' => Phosphor::ClockClockwise,
            'color' => 'warning',
            'action_url' => "/admin/employees/{$this->contract->employee_id}/edit",
        ];
    }
}
