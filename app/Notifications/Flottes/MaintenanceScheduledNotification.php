<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\VehicleMaintenance;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class MaintenanceScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected VehicleMaintenance $maintenance) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $vehicle = $this->maintenance->vehicle;
        $date = $this->maintenance->performed_at->format('d/m/Y');

        return (new MailMessage)
            ->subject("🔧 IMMOBILISATION : Maintenance planifiée pour {$vehicle->license_plate}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("A maintenance operation (**{$this->maintenance->type}**) is scheduled for the vehicle **{$vehicle->brand} {$vehicle->model}** ({$vehicle->license_plate}) that you currently use.")
            ->line('Appointment details :')
            ->line("- **Date :** {$date}")
            ->line("- **Garage / Provider :** {$this->maintenance->supplier->name}")
            ->line('- **Description :** '.($this->maintenance->description ?? 'Standard maintenance check'))
            ->line('You are requested to drop off the vehicle at the garage or coordinate with the fleet manager to secure a replacement utility vehicle.')
            ->action('Détails du véhicule', url('/terrain'))
            ->line('Thank you for your cooperation in keeping our fleet safe and compliant.');
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
            'title' => 'Immobilisation véhicule (Garage)',
            'body' => "Rendez-vous de maintenance pris le {$this->maintenance->performed_at->format('d/m/Y')} pour {$this->maintenance->vehicle->license_plate}.",
            'icon' => Phosphor::CalendarCheck,
            'color' => 'warning',
            'action_url' => '/terrain',
        ];
    }
}
