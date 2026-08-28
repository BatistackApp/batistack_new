<?php

namespace App\Notifications\Chantiers;

use App\Models\Chantiers\Chantier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierMissingLogNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Chantier $chantier) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->info()
            ->icon(Phosphor::PencilLine)
            ->title('Journal de bord manquant')
            ->body("Aucune saisie effectuée aujourd'hui pour le chantier {$this->chantier->reference}.")
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Journal de bord manquant',
            'message' => "Aucune saisie effectuée aujourd'hui pour le chantier {$this->chantier->reference}.",
            'icon' => Phosphor::PencilLine,
            'color' => 'info',
            'action_url' => "/chantiers/chantiers/{$this->chantier->id}",
        ];
    }
}
