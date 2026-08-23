<?php

namespace App\Console\Commands;

use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Models\Tiers\ThirdPartyDocument;
use App\Models\User;
use App\Notifications\Tiers\DocumentExpiredNotification;
use App\Notifications\Tiers\DocumentExpiringNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

#[Signature('app:check-third-party-documents')]
#[Description('Vérifie l\'expiration des documents légaux des tiers et envoie des notifications')]
class CheckThirdPartyDocuments extends Command
{
    public function handle(): int
    {
        $expiredCount = 0;
        $expiringCount = 0;

        $documents = ThirdPartyDocument::where('status', ThirdPartyDocumentStatus::VALID)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', now()->addDays(30))
            ->get();

        foreach ($documents as $document) {
            $daysUntilExpiration = abs(now()->diffInDays($document->expiration_date));

            if ($document->expiration_date->isPast()) {
                $document->update(['status' => ThirdPartyDocumentStatus::EXPIRED]);
                $this->info("Document {$document->id} (Tiers {$document->third_party_id}) est expiré.");

                $this->sendNotification($document, new DocumentExpiredNotification($document));
                $expiredCount++;
            } elseif ($daysUntilExpiration == 7 || $daysUntilExpiration == 30) {
                $this->info("Alerte J-{$daysUntilExpiration} pour Document {$document->id}.");
                $this->sendNotification($document, new DocumentExpiringNotification($document, $daysUntilExpiration));
                $expiringCount++;
            }
        }

        $this->info("Vérification terminée : {$expiredCount} expiré(s), {$expiringCount} bientôt expirant(s).");

        return self::SUCCESS;
    }

    protected function sendNotification($document, $notification): void
    {
        $thirdParty = $document->thirdParty;
        $contact = $thirdParty->primaryContact;

        if ($contact) {
            $contact->notify($notification);

            return;
        }

        if ($thirdParty->email) {
            $notifiable = new AnonymousNotifiable;
            $notifiable->route('mail', $thirdParty->email);
            Notification::send($notifiable, $notification);

            return;
        }

        $admins = User::where('is_admin', true)->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, $notification);
        }
    }
}
