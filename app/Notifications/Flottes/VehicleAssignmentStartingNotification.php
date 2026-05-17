<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\VehicleAssignment;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VehicleAssignmentStartingNotification extends Notification implements ShouldQueue
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
        $startDate = $this->assignment->started_at->format('d/m/Y à H:i');
        $chantierLabel = $this->assignment->chantier ? "pour le chantier **{$this->assignment->chantier->name}**" : 'pour un usage professionnel général';

        return (new MailMessage)
            ->subject('🚗 Votre véhicule de service Batistack pour demain')
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("This is a reminder that the vehicle **{$vehicle->brand} {$vehicle->model}** ({$vehicle->license_plate}) is assigned to you starting tomorrow, **{$startDate}**, {$chantierLabel}.")
            ->line('Important instructions :')
            ->line("- Please complete the hand-over form and check the vehicle's clean condition before departure.")
            ->line('- Remember to input the current odometer reading in your mobile workspace.')
            ->action("Voir l'affectation", url('/terrain'))
            ->line('Drive safely !');
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
            'title' => 'Véhicule assigné pour demain',
            'body' => "Le véhicule {$this->assignment->vehicle->license_plate} vous est confié à partir de demain à ".$this->assignment->started_at->format('H:i').'.',
            'icon' => Phosphor::CarSimple,
            'color' => 'success',
            'action_url' => '/terrain',
        ];
    }
}
