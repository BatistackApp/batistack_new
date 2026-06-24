<?php

namespace App\Notifications\RH;

use App\Models\RH\Abscence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbsencePendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Abscence $absence) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $daysPending = $this->absence->created_at->diffInDays(now());

        return (new MailMessage)
            ->subject("Rappel: Absence en attente - {$this->absence->employee->getFullName()}")
            ->line("L'absence de {$this->absence->employee->getFullName()} est en attente depuis {$daysPending} jours")
            ->line("Type: {$this->absence->type}")
            ->line("Date: {$this->absence->start_date->format('d/m/Y')}")
            ->line("Raison: {$this->absence->reason}")
            ->line('Veuillez approuver ou rejeter cette demande.')
            ->action('Approuver/Rejeter', url('/rh/absences'))
            ->line('Action requise');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'absence_id' => $this->absence->id,
            'employee_id' => $this->absence->employee_id,
            'employee_name' => $this->absence->employee->getFullName(),
            'type' => $this->absence->type,
            'date' => $this->absence->start_date,
            'days_pending' => $this->absence->created_at->diffInDays(now()),
        ];
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
