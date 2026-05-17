<?php

namespace App\Notifications\Chantiers;

use App\Models\Chantiers\Chantier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierBudgetAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Chantier $chantier, protected array $metrics) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $percent = round($this->metrics['hours']['percent'], 1);

        return (new MailMessage)
            ->subject("⚠️ Alerte Budget : {$this->chantier->name} ({$percent}%)")
            ->greeting('Seuil de vigilance atteint')
            ->line("Le chantier **{$this->chantier->name}** a consommé plus de **{$percent}%** de son enveloppe d'heures prévue.")
            ->line('Statistiques actuelles :')
            ->line("- Budget prévu : {$this->metrics['hours']['budget']} h")
            ->line("- Consommé réel : {$this->metrics['hours']['real']} h")
            ->action('Consulter le bilan financier', url("/chantiers/chantiers/{$this->chantier->id}"))
            ->line('Une analyse de la productivité est recommandée pour préserver la marge.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->warning()
            ->title('Alerte Budget Heures')
            ->body('Consommation à '.round($this->metrics['hours']['percent'])."% pour {$this->chantier->reference}.")
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Alerte Budget Heures',
            'message' => 'Consommation à '.round($this->metrics['hours']['percent'])."% pour {$this->chantier->reference}.",
            'icon' => Phosphor::ChartBar,
            'color' => 'warning',
        ];
    }
}
