<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\VehicleAssignment;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class OverdueAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected VehicleAssignment $assignment) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $vehicle = $this->assignment->vehicle;
        $employee = $this->assignment->employee;
        $expectedReturn = $this->assignment->ended_at->format('d/m/Y à H:i');

        return (new MailMessage)
            ->subject("🕒 Retard Restitution Véhicule - {$employee->full_name}")
            ->greeting('Alerte Restitution Flotte')
            ->line("Le véhicule **{$vehicle->brand} {$vehicle->model}** immatriculé **{$vehicle->license_plate}** n'a pas été restitué au dépôt à l'heure convenue.")
            ->line('Informations de planification :')
            ->line("- **Conducteur dépositaire :** {$employee->full_name} (Tél : {$employee->phone})")
            ->line("- **Date de retour théorique :** {$expectedReturn}")
            ->line("- **Motif d'affectation initial :** ".($this->assignment->purpose ?? 'Non spécifié'))
            ->action("Consulter l'affectation", url('/chantiers/vehicle-assignments'))
            ->line("Ce retard peut bloquer les équipes prévues pour utiliser ce véhicule sur d'autres opérations demain matin. Merci de prendre contact avec le compagnon.");
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
            'title' => 'Retard restitution véhicule',
            'body' => "Le véhicule {$this->assignment->vehicle->license_plate} confié à {$this->assignment->employee->full_name} n'est pas rentré au dépôt.",
            'icon' => Phosphor::ClockAfternoon,
            'color' => 'danger',
            'action_url' => '/chantiers/vehicle-assignments',
        ];
    }
}
