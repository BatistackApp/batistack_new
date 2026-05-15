<?php

namespace App\Jobs\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Services\Core\GoogleMapsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GeocodeChantierAddressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Chantier $chantier) {}

    public function handle(GoogleMapsService $googleMaps): void
    {
        $address = $this->chantier->full_address;

        try {
            $coords = $googleMaps->geocodeAddress($address);

            if ($coords) {
                $this->chantier->updateQuietly([
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lng'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Erreur de géocodage pour le chantier #{$this->chantier->id} : ".$e->getMessage());
        }
    }
}
