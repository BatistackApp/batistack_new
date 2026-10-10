<?php

namespace App\Observers\Tiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\Contact;
use App\Models\User;
use App\Notifications\Tiers\WelcomeCustomerNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContactObserver
{
    public function created(Contact $contact): void
    {
        if (! empty($contact->email)
            && in_array($contact->thirdParty->type, [ThirdPartyType::CLIENT, ThirdPartyType::SUBCONTRACTOR], true)
        ) {
            $user = User::create([
                'name' => $contact->full_name,
                'email' => $contact->email,
                'password' => \Hash::make(Str::random(12)),
                'is_admin' => false,
                'is_tiers' => true,
            ]);

            // The account/company mapping must exist before assigning the contact,
            // because the database enforces the composite foreign key.
            $this->ensureUserCompanyMapping($contact, $user->id);
            $contact->updateQuietly(['user_id' => $user->id]);

            $user->notify(new WelcomeCustomerNotification($contact));
        }
    }

    public function saving(Contact $contact): void
    {
        if ($contact->is_primary) {
            $contact->thirdParty->contacts()
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }

        if ($contact->user_id !== null) {
            $this->ensureUserCompanyMapping($contact, (int) $contact->user_id);
        }
    }

    private function ensureUserCompanyMapping(Contact $contact, int $userId): void
    {
        $existingCompanyId = DB::table('user_third_party_accounts')
            ->where('user_id', $userId)
            ->value('third_party_id');

        if ($existingCompanyId !== null) {
            if ((int) $existingCompanyId !== (int) $contact->third_party_id) {
                throw ValidationException::withMessages([
                    'user_id' => 'Ce compte est déjà associé à une autre entreprise.',
                ]);
            }

            return;
        }

        DB::table('user_third_party_accounts')->insert([
            'user_id' => $userId,
            'third_party_id' => $contact->third_party_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
