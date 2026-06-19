<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\TrafficFine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrafficFineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected TrafficFine $fine, protected int $daysOverdue = 0) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("🚨 RAPPEL AMENDE EN RETARD - {$this->fine->reference}")
            ->greeting('Rappel Important')
            ->line("Amende PV **{$this->fine->reference}** en attente depuis {$this->daysOverdue} jours.")
            ->line("Véhicule : **{$this->fine->vehicle->license_plate}**")
            ->line('Montant : **'.number_format($this->fine->amount, 2, ',', ' ').'€**')
            ->action('Payer amende', url('/flottes'))
            ->line('⚠️ Risque de majoration si dépassement délai légal (45 jours).');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Amende en retard - Rappel',
            'body' => "PV {$this->fine->reference} en attente depuis {$this->daysOverdue}j pour {$this->fine->vehicle->license_plate}.",
            'icon' => 'heroicon-o-exclamation',
            'color' => 'danger',
            'action_url' => '/flottes',
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
