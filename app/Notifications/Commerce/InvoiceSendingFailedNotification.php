<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\CustomerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class InvoiceSendingFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected CustomerInvoice $invoice,
        protected Throwable $exception
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("❌ ERREUR CRITIQUE : Envoi facture échoué - {$this->invoice->reference}")
            ->greeting('Erreur Système')
            ->line("L'envoi de la facture n°{$this->invoice->reference} par email a échoué de manière définitive.")
            ->line("Après **3 tentatives**, le système n'a pas pu transmettre le document au client.")
            ->line('')
            ->line("**Détails de l'incident :**")
            ->line("• **Facture** : {$this->invoice->reference}")
            ->line("• **Client** : {$this->invoice->client->name}")
            ->line('• **Contact email** : '.$this->invoice->client->primaryContact?->email ?? 'Non défini')
            ->line("• **Statut facture** : {$this->invoice->status->getLabel()}")
            ->line('• **Montant** : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' € TTC')
            ->line('')
            ->line('**Erreur technique :**')
            ->line('```'.substr($this->exception->getMessage(), 0, 200).'...```')
            ->line('')
            ->line('**Actions recommandées :**')
            ->line("1. Vérifier que l'adresse email du client est correcte")
            ->line('2. Vérifier que le serveur de mail est accessible')
            ->line('3. Envoyer manuellement la facture au client')
            ->line("4. Corriger le problème et relancer l'envoi")
            ->action('Voir la facture', url("/commerce/invoices/{$this->invoice->id}"))
            ->line('**Veuillez intervenir rapidement pour assurer la communication avec le client.**');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->danger()
            ->title("Erreur : Envoi facture échoué - {$this->invoice->reference}")
            ->body("Impossible d'envoyer la facture à {$this->invoice->client->name} - Intervention manuelle requise")
            ->getDatabaseMessage();
    }
}
