<?php

namespace App\Jobs\Tiers;

use App\Exceptions\Tiers\TiersModuleException;
use App\Models\Tiers\Address;
use App\Models\User;
use App\Services\Core\GoogleMapsService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class GeocodeAddressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Address $address,
        protected ?User $user = null,
    ) {}

    public function handle(GoogleMapsService $googleMapsService): void
    {
        $fullAddress = "{$this->address->street}, {$this->address->zip_code} {$this->address->city}";

        try {
            $coords = $googleMapsService->geocodeAddress($fullAddress);

            if ($coords) {
                // On utilise saveQuietly pour éviter de boucler sur l'observer
                $this->address->latitude = $coords['lat'];
                $this->address->longitude = $coords['lng'];
                $this->address->saveQuietly();
            } else {
                Notification::make()
                    ->warning()
                    ->title('Géocodage Impossible')
                    ->body("L'adresse du tiers n'a pas pu être localisée précisément sur la carte.")
                    ->icon(Phosphor::XCircle)
                    ->sendToDatabase($this->user);
            }
        } catch (\Throwable $e) {
            Log::error("Erreur lors du géocodage de l'adresse ID {$this->address->id}: ".$e->getMessage());
            throw (new TiersModuleException("Erreur lors du géocodage de l'adresse ID {$this->address->id}", 500, $e))->notify();
        }
    }
}
