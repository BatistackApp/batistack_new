<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\Vehicle;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class MilestoneMaintenanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Vehicle $vehicle) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🔧 Seuil de révision atteint - Véhicule {$this->vehicle->reference}")
            ->greeting('Planification Maintenance Préventive')
            ->line("Le véhicule **{$this->vehicle->brand} {$this->vehicle->model}** ({$this->vehicle->license_plate}) nécessite un passage en atelier.")
            ->line('Le kilométrage actuel est de **'.number_format($this->vehicle->odometer, 1, ',', ' ').' km**.')
            ->line("L'analyse préventive indique qu'il a dépassé son jalon d'entretien réglementaire ou constructeur (seuil des 20 000 km d'intervalle écoulés).")
            ->action('Planifier un entretien', url("/chantiers/vehicles/{$this->vehicle->id}"))
            ->line("Une révision rigoureuse permet de préserver la garantie constructeur, d'assurer la sécurité des compagnons et d'éviter les pannes immobilisantes sur vos chantiers.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->color($this->toArray($notifiable)['color'])
            ->title($this->toArray($notifiable)['title'])
            ->body($this->toArray($notifiable)['body'])
            ->icon($this->toArray($notifiable)['icon'])
            ->actions([
                Action::make('contrat_view')
                    ->label('Fiche Vehicule')
                    ->url($this->toArray($notifiable)['action_url']),
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Entretien requis',
            'body' => "Le véhicule {$this->vehicle->reference} ({$this->vehicle->license_plate}) a dépassé son seuil kilométrique de révision.",
            'icon' => Phosphor::Wrench,
            'color' => 'info',
            'action_url' => "/chantiers/vehicles/{$this->vehicle->id}",
        ];
    }
}
