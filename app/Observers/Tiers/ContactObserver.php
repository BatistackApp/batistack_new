<?php

namespace App\Observers\Tiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\Contact;
use App\Models\User;
use App\Notifications\Tiers\WelcomeCustomerNotification;
use Illuminate\Support\Str;

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
    }
}
