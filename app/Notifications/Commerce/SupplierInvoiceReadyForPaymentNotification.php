<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\SupplierInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierInvoiceReadyForPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SupplierInvoice $invoice
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->success()
            ->subject("✓ BON À PAYER : Facture n°{$this->invoice->reference}")
            ->greeting('Facture Validée')
            ->line("La facture n°{$this->invoice->reference} du fournisseur **{$this->invoice->supplier->name}** a réussi le contrôle 3 voies.")
            ->line('**Elle est maintenant prête pour le paiement.**')
            ->line('')
            ->line('**Informations de paiement :**')
            ->line("• **Fournisseur** : {$this->invoice->supplier->name}")
            ->line("• **Numéro facture** : {$this->invoice->reference}")
            ->line('• **Montant HT** : '.number_format($this->invoice->amount_ht, 2, ',', ' ').' €')
            ->line('• **Montant TTC** : '.number_format($this->invoice->amount_ttc, 2, ',', ' ').' €')
            ->line("• **Échéance de paiement** : {$this->invoice->due_date->format('d/m/Y')}")
            ->line('• **Mode de paiement** : À définir selon les conditions du fournisseur')
            ->line('')
            ->line('**Résultat du contrôle :**')
            ->line('✓ Quantité facturée ≤ Quantité reçue')
            ->line('✓ Prix facturé ≤ Prix BC + tolérance')
            ->line('✓ Aucune anomalie détectée')
            ->action('Enregistrer le paiement', url("/commerce/supplier-invoices/{$this->invoice->id}"))
            ->line("Procédez au paiement avant la date d'échéance.");
    }

    public function toDatabase($notifiable): array
    {
        return [];
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
