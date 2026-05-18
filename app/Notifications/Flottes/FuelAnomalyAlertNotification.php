<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\Vehicle;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class FuelAnomalyAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Vehicle $vehicle, protected string $anomalyMessage) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("🚨 ALERTE CARBURANT : Anomalie sur le véhicule {$this->vehicle->reference}")
            ->greeting("Détection d'Anomalie Énergétique")
            ->line("Le moteur d'analyse des cartes carburant a détecté une anomalie de consommation pour le véhicule **{$this->vehicle->brand} {$this->vehicle->model}** ({$this->vehicle->license_plate}).")
            ->line("Message d'alerte du système :")
            ->line("**{$this->anomalyMessage}**")
            ->action("Analyser l'actif", url("/chantiers/vehicles/{$this->vehicle->id}"))
            ->line("Cette alerte automatique peut être due à un siphonnage de réservoir sur chantier, un usage personnel non autorisé ou une erreur de saisie d'odomètre par le conducteur en station. Veuillez mener une investigation.");
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
            'title' => 'Anomalie carburant détectée',
            'body' => "Une anomalie énergétique a été détectée pour le véhicule {$this->vehicle->reference}.",
            'icon' => Phosphor::GasPump,
            'color' => 'danger',
            'action_url' => "/chantiers/vehicles/{$this->vehicle->id}",
        ];
    }
}
