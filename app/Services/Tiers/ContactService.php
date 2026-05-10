<?php

namespace App\Services\Tiers;

use App\Exceptions\Tiers\TiersModuleException;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactService
{
    /**
     * Enregistre ou met à jour un contact tiers avec détection de doublons.
     */
    public function syncContact(ThirdParty $thirdParty, array $data): Contact
    {
        try {
            return DB::transaction(function () use ($thirdParty, $data) {
                $contact = $thirdParty->contacts()->updateOrCreate(
                    ['email' => $data['email']],
                    array_merge($data, ['is_active' => true])
                );

                if (! empty($data['is_primary']) && $data['is_primary']) {
                    $this->setAsPrimary($contact);
                }

                return $contact;
            });
        } catch (\Throwable $e) {
            Log::critical('Création du contact impossible: '.$e->getMessage(), (array) $e);
            $exception = new TiersModuleException('Création du contact impossible', 500, $e);
            $exception->notify();
            throw $exception;
        }
    }

    /**
     * Définit l'exclusivité du contact principal.
     */
    public function setAsPrimary(Contact $contact): void
    {
        try {
            DB::transaction(function () use ($contact) {
                Contact::where('third_party_id', $contact->third_party_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);

                $contact->update(['is_primary' => true]);
            });
        } catch (\Throwable $e) {
            Log::critical('Mise à jour du contact principal impossible: '.$e->getMessage(), (array) $e);
            $exception = new TiersModuleException('Mise à jour du contact principal impossible', 500, $e);
            $exception->notify();
            throw $exception;
        }
    }

    /**
     * Archive un contact proprement sans suppression (Soft Archive).
     */
    public function archive(Contact $contact, string $reason): void
    {
        $contact->update([
            'is_active' => false,
            'is_primary' => false,
            'metadata' => array_merge($contact->metadata ?? [], [
                'archived_at' => now()->toDateTimeString(),
                'archive_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Transfert de responsabilités d'un contact à un autre (Onboarding/Offboarding).
     */
    public function reassignResponsibilities(Contact $from, Contact $to): void
    {
        try {
            DB::transaction(function () use ($from, $to) {
                if ($from->is_primary) {
                    $this->setAsPrimary($to);
                }

                $this->archive($from, "Remplacé par {$to->full_name} (Transfert de poste)");
            });
        } catch (\Throwable $e) {
            Log::critical('Transfert de responsabilités impossible: '.$e->getMessage(), (array) $e);
            $exception = new TiersModuleException('Transfert de responsabilités impossible', 500, $e);
            $exception->notify();
            throw $exception;
        }
    }
}
