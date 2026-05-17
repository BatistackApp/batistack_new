<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\VehicleContract;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

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
            ->subject("📅 Échéance contrat Flotte à J-{$daysRemaining} - {$vehicle->reference}")
            ->greeting('Alerte Contrat Flotte')
            ->line('Le contrat de type **'.ucfirst($this->contract->type)."** pour le véhicule **{$vehicle->brand} {$vehicle->model}** ({$vehicle->license_plate}) arrive à expiration.")
            ->line('Détails administratifs :')
            ->line("- **N° de police/contrat :** {$this->contract->policy_number}")
            ->line("- **Date d'échéance :** {$this->contract->end_date->format('d/m/Y')}")
            ->line('- **Coût annuel HT :** '.number_format($this->contract->annual_cost_ht, 2, ',', ' ').' €')
            ->action('Gérer le contrat', url("/chantiers/vehicles/{$vehicle->id}/edit"))
            ->line("Merci de prendre contact avec le partenaire/assureur **{$this->contract->supplier->name}** pour planifier le renouvellement ou la restitution de l'actif.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->warning()
            ->title('Echéance contrat Flotte')
            ->body("Le contrat {$this->contract->type} pour {$this->contract->vehicle->license_plate} expire le ".$this->contract->end_date->format('d/m/Y').'.')
            ->icon(Phosphor::CalendarX)
            ->actions([
                Action::make('contrat_view')
                    ->label('Fiche Vehicule')
                    ->url("/chantiers/vehicles/{$this->contract->vehicle_id}/edit")
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Échéance contrat Flotte',
            'body' => "Le contrat {$this->contract->type} pour {$this->contract->vehicle->license_plate} expire le ".$this->contract->end_date->format('d/m/Y').'.',
            'icon' => Phosphor::CalendarX,
            'color' => 'warning',
            'action_url' => "/chantiers/vehicles/{$this->contract->vehicle_id}/edit",
        ];
    }
}
