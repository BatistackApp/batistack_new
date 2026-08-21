<?php

namespace App\Notifications;

use App\Models\Locations\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalExpirationAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RentalContract $contract,
        public int $daysUntilExpiration
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Alerte échéance location - Contrat {$this->contract->reference}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Le contrat de location **{$this->contract->reference}** arrive à échéance dans **{$this->daysUntilExpiration} jour(s)**.")
            ->line('Chantier concerné : '.($this->contract->chantier?->name ?? 'N/A'))
            ->line("Date d'échéance : {$this->contract->end_date?->format('d/m/Y')}")
            ->action('Voir le contrat', url("/locations/rental-contracts/{$this->contract->id}"))
            ->line('Merci d\'anticiper la restitution ou le renouvellement du matériel.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rental_expiration',
            'contract_id' => $this->contract->id,
            'contract_reference' => $this->contract->reference,
            'chantier_name' => $this->contract->chantier?->name,
            'days_until_expiration' => $this->daysUntilExpiration,
            'end_date' => $this->contract->end_date?->format('Y-m-d'),
            'url' => "/locations/rental-contracts/{$this->contract->id}",
        ];
    }
}
