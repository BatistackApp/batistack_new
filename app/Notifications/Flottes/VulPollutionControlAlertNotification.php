<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\Vehicle;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VulPollutionControlAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Vehicle $vehicle) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $dueDate = $this->vehicle->pollution_control_due_at->format('d/m/Y');

        return (new MailMessage)
            ->warning()
            ->subject("🔧 CONFORMITÉ VUL : Contrôle pollution requis - {$this->vehicle->reference}")
            ->greeting('Bonjour,')
            ->line("Le véhicule utilitaire léger **{$this->vehicle->brand} {$this->vehicle->model}** immatriculé **{$this->vehicle->license_plate}** est soumis au contrôle pollution annuel obligatoire.")
            ->line("La date limite de validité est fixée au : **{$dueDate}**.")
            ->action('Planifier le passage au centre', url("/chantiers/vehicles/{$this->vehicle->id}"))
            ->line("Le défaut de contrôle pollution expose l'entreprise à une amende de classe 4 et à l'immobilisation du véhicule lors des contrôles routiers.");
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
            'title' => 'Contrôle pollution requis',
            'body' => 'Échéance pollution le '.$this->vehicle->pollution_control_due_at->format('d/m/Y')." pour {$this->vehicle->license_plate}.",
            'icon' => Phosphor::Wrench,
            'color' => 'warning',
            'action_url' => "/chantiers/vehicles/{$this->vehicle->id}",
        ];
    }
}
