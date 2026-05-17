<?php

namespace App\Notifications\Flottes;

use App\Models\Flottes\TrafficFine;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TrafficFineReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected TrafficFine $fine) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $vehicle = $this->fine->vehicle;
        $infractionDate = $this->fine->infraction_at->format('d/m/Y à H:i');

        return (new MailMessage)
            ->error()
            ->subject("⚠️ Signalement d'infraction routière - Véhicule {$vehicle->license_plate}")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Nous avons reçu un procès-verbal de contravention concernant le véhicule **{$vehicle->brand} {$vehicle->model}** immatriculé **{$vehicle->license_plate}**.")
            ->line("D'après nos registres de planification d'équipages, vous étiez le conducteur désigné de ce véhicule le **{$infractionDate}**.")
            ->line("Détails de l'infraction :")
            ->line("- **Référence du PV :** {$this->fine->reference}")
            ->line("- **Montant de l'amende :** ".number_format($this->fine->amount, 2, ',', ' ').' €')
            ->line("- **Points retirés (estimation) :** {$this->fine->points_deducted} point(s)")
            ->action('Consulter la contravention', url('/terrain'))
            ->line("Conformément à l'article L. 121-6 du Code de la route, l'entreprise est dans l'obligation de désigner le conducteur réel auprès de l'ANTAI. Vous recevrez le PV officiel à votre adresse personnelle pour consignation ou paiement.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->danger()
            ->title('Contravention routière reçue')
            ->body("PV {$this->fine->reference} rattaché le ".$this->fine->infraction_at->format('d/m/Y')." pour le véhicule {$this->fine->vehicle->license_plate}.")
            ->icon(Phosphor::WarningOctagon)
            ->actions([
                Action::make('contravention')
                    ->label('Consulter la contravention')
                    ->url('/terrain'),
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Contravention routière reçue',
            'body' => "PV {$this->fine->reference} rattaché le ".$this->fine->infraction_at->format('d/m/Y')." pour le véhicule {$this->fine->vehicle->license_plate}.",
            'icon' => Phosphor::WarningOctagon,
            'color' => 'danger',
            'action_url' => '/terrain',
        ];
    }
}
