<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\FleetExpense;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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

        return (new MailMessage)
            ->error()
            ->subject("🚨 ALERTE FRAUDE : Frais de route suspect - {$vehicle->reference}")
            ->greeting('Bonjour,')
            ->line("Transaction suspecte détectée pour le véhicule **{$vehicle->brand} {$vehicle->model}** ({$vehicle->license_plate}).")
            ->line('Détails :')
            ->line('- **Montant :** '.number_format($this->expense->amount_ttc, 2, ',', ' ').'€ TTC')
            ->line("- **Type :** {$this->expense->getTypeLabel()}")
            ->line("- **Lieu :** {$this->expense->merchant_name}")
            ->line("- **Date :** {$this->expense->spent_at->format('d/m/Y H:i')}")
            ->line("**Motif de l'alerte :**")
            ->line($this->expense->suspicion_reason)
            ->action('Enquêter', url("/flottes/vehicles/{$vehicle->id}"))
            ->line('Veuillez vérifier la cohérence avec l\'activité réelle.');
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
            'body' => 'Usage anormal détecté le '.$this->expense->spent_at->format('d/m/Y')." pour {$this->expense->vehicle->license_plate}.",
            'icon' => 'heroicon-o-exclamation-triangle',
            'color' => 'danger',
            'action_url' => "/flottes/vehicles/{$this->expense->vehicle_id}",
        ];
    }
}
