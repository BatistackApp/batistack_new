<?php

namespace App\Observers\Tiers;

use App\Jobs\Tiers\GeocodeAddressJob;
use App\Models\Tiers\Address;

/**
 * Observer pour le modèle Address.
 * Déclenche le géocodage de manière asynchrone pour optimiser les performances.
 */
class AddressObserver
{
    public function saving(Address $address): void
    {
        if ($address->is_default) {
            $address->thirdParty->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }
    }

    public function saved(Address $address): void
    {
        // On ne déclenche le job que si l'adresse a été modifiée
        if ($address->wasRecentlyCreated || $address->wasChanged(['street', 'zip_code', 'city'])) {
            GeocodeAddressJob::dispatch($address, auth()->user());
        }
    }
}
