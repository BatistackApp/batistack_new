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
            $others = $address->thirdParty->addresses()
                ->where('id', '!=', $address->id)
                ->where(['is_default' => true])
                ->get();

            // On désactive les événements pour chaque modèle trouvé
            foreach ($others as $other) {
                $other->withoutEvents(function () use ($other) {
                    $other->update(['is_default' => false]);
                });
            }
        }
    }

    public function saved(Address $address): void
    {
        // Vérifier si c'est une création
        if ($address->wasRecentlyCreated) {
            GeocodeAddressJob::dispatch($address, auth()->user());

            return;
        }

        // Vérifier si les champs géographiques ont changé en comparant avec l'état original
        $original = $address->getOriginal();

        $streetChanged = $original['street'] !== $address->street;
        $zipChanged = $original['zip_code'] !== $address->zip_code;
        $cityChanged = $original['city'] !== $address->city;

        if ($streetChanged || $zipChanged || $cityChanged) {
            GeocodeAddressJob::dispatch($address, auth()->user());
        }
    }
}
