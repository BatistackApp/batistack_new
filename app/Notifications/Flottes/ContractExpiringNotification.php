<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\VehicleContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected VehicleContract $contract) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $vehicle = $this->contract->vehicle;
        $daysRemaining = now()->diffInDays($this->contract->end_date);

        return (new MailMessage)
            ->subject("📅 Échéance contrat - {$vehicle->reference} (J-{$daysRemaining})")
            ->greeting('Alerte Contrat Flotte')
            ->line("Le contrat **{$this->contract->type}** pour le véhicule **{$vehicle->brand} {$vehicle->model}** ({$vehicle->license_plate}) arrive à expiration.")
            ->line('Détails :')
            ->line("- **N° police :** {$this->contract->policy_number}")
            ->line("- **Expiration :** {$this->contract->end_date->format('d/m/Y')}")
            ->line('- **Coût annuel :** '.number_format($this->contract->annual_cost_ht, 2, ',', ' ').'€')
            ->action('Gérer contrat', url("/flottes/vehicles/{$vehicle->id}"))
            ->line("Contactez **{$this->contract->supplier->name}** pour renouvellement ou restitution.");
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Échéance contrat Flotte',
            'body' => "Contrat {$this->contract->type} pour {$this->contract->vehicle->license_plate} expire le {$this->contract->end_date->format('d/m/Y')}.",
            'icon' => 'heroicon-o-calendar',
            'color' => 'warning',
            'action_url' => "/flottes/vehicles/{$this->contract->vehicle_id}",
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
