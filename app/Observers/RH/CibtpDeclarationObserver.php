<?php

namespace App\Observers\RH;

use App\Models\RH\CibtpDeclaration;
use App\Models\User;
use App\Notifications\Commerce\ChantierDelayAvenantNotification;
use Illuminate\Support\Facades\Notification;

class CibtpDeclarationObserver
{
    /**
     * Handle the CibtpDeclaration "updated" event.
     */
    public function updated(CibtpDeclaration $cibtpDeclaration): void
    {
        // Vérifier si le statut a changé et est passé en "validated"
        if ($cibtpDeclaration->isDirty('status') && $cibtpDeclaration->status === 'validated') {

            // Calcul des jours calendaires perdus (8h = 1 jour, arrondi supérieur)
            $lostDays = (int) ceil($cibtpDeclaration->total_lost_hours / 8);

            if ($lostDays > 0) {
                $chantier = $cibtpDeclaration->chantier;

                // Décalage calendaire simple
                if ($chantier && $chantier->end_date_preview) {
                    $chantier->end_date_preview = $chantier->end_date_preview->addDays($lostDays);
                    $chantier->saveQuietly();
                }

                if ($chantier) {
                    $notification = new ChantierDelayAvenantNotification($chantier, $lostDays);

                    // 1. Notifier le Responsable Chantier
                    if ($chantier->manager) {
                        $chantier->manager->notify($notification);
                    }

                    // 2. Notifier le Responsable commerce (Admins en fallback)
                    $commerceUsers = User::admin()->get();
                    Notification::send($commerceUsers, $notification);

                    // 3. Notifier le Client
                    if ($chantier->client && $chantier->client->email) {
                        // On force uniquement le canal mail pour le client externe
                        Notification::route('mail', $chantier->client->email)
                            ->notify($notification);
                    }
                }
            }
        }
    }
}
