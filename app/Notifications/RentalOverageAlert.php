<?php

namespace App\Notifications;

use App\Models\Locations\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalOverageAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RentalContract $contract,
        public int $daysOverdue,
        public float $penaltyAmount,
        public float $totalPenaltyAmount
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("ALERTE DÉPASSEMENT LOCATION - Contrat {$this->contract->reference}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le contrat de location **{$this->contract->reference}** est en dépassement depuis **{$this->daysOverdue} jour(s)**.")
            ->line('Chantier concerné : '.($this->contract->chantier?->name ?? 'N/A'))
            ->line("Date d'échéance prévue : {$this->contract->expected_end_date?->format('d/m/Y')}")
            ->line("Pénalité journalière : {$this->contract->daily_penalty_rate} €/jour")
            ->line("Pénalité de cette période : {$this->penaltyAmount} €")
            ->line("**Pénalité cumulée totale : {$this->totalPenaltyAmount} €**")
            ->action('Voir le contrat', url("/locations/rental-contracts/{$this->contract->id}"))
            ->line('Merci de prendre les dispositions nécessaires (restitution ou renouvellement).');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rental_overage',
            'contract_id' => $this->contract->id,
            'contract_reference' => $this->contract->reference,
            'chantier_name' => $this->contract->chantier?->name,
            'days_overdue' => $this->daysOverdue,
            'penalty_amount' => $this->penaltyAmount,
            'total_penalty_amount' => $this->totalPenaltyAmount,
            'expected_end_date' => $this->contract->expected_end_date?->format('Y-m-d'),
            'url' => "/locations/rental-contracts/{$this->contract->id}",
        ];
    }
}
