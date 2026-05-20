<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\SupplierInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierInvoiceAuditFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SupplierInvoice $invoice,
        protected array $disputes
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->error()
            ->subject("🚨 LITIGE FACTURE FOURNISSEUR : {$this->invoice->reference}")
            ->greeting('Alerte Contrôle de Facturation')
            ->line("La facture n°{$this->invoice->reference} du fournisseur **{$this->invoice->supplier->name}** présente des anomalies détectées lors du contrôle 3 voies.")
            ->line('**Le paiement a été automatiquement bloqué en attente de votre intervention.**')
            ->line('')
            ->line('**Litiges détectés :**');

        foreach ($this->disputes as $dispute) {
            $mail->line('❌ '.$dispute);
        }

        return $mail
            ->line('')
            ->line('**Montant de la facture** : '.number_format($this->invoice->amount_ttc, 2, ',', ' ').' € TTC')
            ->line('**Statut** : LITIGE (Paiement bloqué)')
            ->action('Vérifier la facture', url("/commerce/supplier-invoices/{$this->invoice->id}"))
            ->line('Veuillez :')
            ->line('1. Examiner les litiges signalés ci-dessus')
            ->line('2. Contacter le fournisseur pour rectification ou justification')
            ->line('3. Valider manuellement ou rejeter la facture')
            ->line('4. Débloquer le paiement une fois la situation clarifiée');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->danger()
            ->title("LITIGE facture fournisseur : {$this->invoice->reference}")
            ->body('Contrôle 3 voies échoué - Paiement bloqué')
            ->getDatabaseMessage();
    }
}
