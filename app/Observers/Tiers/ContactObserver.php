<?php

namespace App\Observers\Tiers;

use App\Models\Tiers\Contact;

class ContactObserver
{
    public function saving(Contact $contact): void
    {
        if ($contact->is_primary) {
            $contact->thirdParty->contacts()
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }
    }
}
