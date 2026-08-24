<?php

namespace App\Notifications\Subcontractor;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\SubcontractorSituation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FactureStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SubcontractorSituation $situation,
        protected InvoiceStatus $oldStatus,
        protected InvoiceStatus $newStatus,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Statut facture mis à jour : {$this->situation->reference}")
            ->greeting('Mise à jour de votre facture')
            ->line("La facture n°**{$this->situation->reference}** a changé de statut.")
            ->line("**Ancien statut** : {$this->oldStatus->getLabel()}")
            ->line("**Nouveau statut** : {$this->newStatus->getLabel()}")
            ->line('**Montant HT** : '.number_format($this->situation->total_ht, 2, ',', ' ').' €')
            ->action('Voir la facture', url("/sous-traitant/factures/{$this->situation->id}"))
            ->line("L'équipe Batistack.");
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title("Facture {$this->situation->reference} : {$this->newStatus->getLabel()}")
            ->body('Montant : '.number_format($this->situation->total_ht, 2, ',', ' ').' € HT')
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
