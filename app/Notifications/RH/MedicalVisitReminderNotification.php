<?php

namespace App\Notifications\RH;

use App\Models\RH\MedicalVisit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicalVisitReminderNotification extends Notification
{
    public function __construct(protected MedicalVisit $visit) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🩺 Rappel : Visite Médicale à programmer')
            ->line("Une visite médicale de type {$this->visit->type->getLabel()} est à prévoir prochainement.")
            ->line("Date d'échéance : {$this->visit->next_due_date->format('d/m/Y')}.")
            ->action('Prendre RDV', url("/rh/employees/{$this->visit->employee_id}/edit"));
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->warning()
            ->title('🩺 Rappel : Visite Médicale à programmer')
            ->body("Une visite médicale de type {$this->visit->type->getLabel()} est à prévoir prochainement.")
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
