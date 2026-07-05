<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-third-party-documents')]
#[Description('Command description')]
class CheckThirdPartyDocuments extends Command
{
    public function handle()
    {
        $documents = \App\Models\Tiers\ThirdPartyDocument::where('status', \App\Enums\Tiers\ThirdPartyDocumentStatus::VALID)
            ->whereNotNull('expiration_date')
            ->get();

        foreach ($documents as $document) {
            $daysUntilExpiration = now()->diffInDays($document->expiration_date, false);

            if ($daysUntilExpiration <= 0) {
                $document->update(['status' => \App\Enums\Tiers\ThirdPartyDocumentStatus::EXPIRED]);
                $this->info("Document {$document->id} (Tiers {$document->third_party_id}) est expiré.");
                // Here we would dispatch an Event or a Notification:
                // Notification::route('mail', $document->thirdParty->email)->notify(new DocumentExpiredNotification($document));
            } elseif ($daysUntilExpiration == 7 || $daysUntilExpiration == 30) {
                $this->info("Envoi alerte pour Document {$document->id} ({$daysUntilExpiration} jours restants).");
                // Here we would dispatch an Event or a Notification:
                // Notification::route('mail', $document->thirdParty->email)->notify(new DocumentExpiringNotification($document, $daysUntilExpiration));
            }
        }

        $this->info('Vérification des documents terminée.');
    }
}
