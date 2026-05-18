<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\FleetExpense;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class FleetExpenseAnomalyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected FleetExpense $expense) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $vehicle = $this->expense->vehicle;
        $typeLabel = $this->expense->type->getLabel();

        return (new MailMessage)
            ->error()
            ->subject("🚨 ALERTE FRAIS DE ROUTE : Usage suspect pour le véhicule {$vehicle->reference}")
            ->greeting('Bonjour,')
            ->line("Une transaction suspecte de type **{$typeLabel}** a été enregistrée pour le véhicule **{$vehicle->brand} {$vehicle->model}** ({$vehicle->license_plate}).")
            ->line('Détails financiers :')
            ->line('- **Montant :** '.number_format($this->expense->amount_ttc, 2, ',', ' ').' € TTC')
            ->line("- **Marchand / Lieu :** {$this->expense->merchant_name} (".($this->expense->description ?? 'Non précisé').')')
            ->line("- **Date de transaction :** {$this->expense->spent_at->format('d/m/Y à H:i')}")
            ->line("Motif de l'alerte :")
            ->line("**{$this->expense->suspicion_reason}**")
            ->action("Mener l'enquête", url("/chantiers/vehicles/{$vehicle->id}"))
            ->line("Nous vous invitons à vérifier la cohérence de cette transaction avec l'activité réelle du collaborateur.");
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
            'title' => 'Alerte frais de route suspect',
            'body' => 'Usage anormal détecté le '.$this->expense->spent_at->format('d/m/Y')." pour le véhicule {$this->expense->vehicle->license_plate}.",
            'icon' => Phosphor::WarningOctagon,
            'color' => 'danger',
            'action_url' => "/chantiers/vehicles/{$this->expense->vehicle_id}",
        ];
    }
}
