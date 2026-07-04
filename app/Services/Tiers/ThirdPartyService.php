<?php

namespace App\Services\Tiers;

use App\Enums\Tiers\AddressType;
use App\Exceptions\Tiers\TiersModuleException;
use App\Models\Tiers\Address;
use App\Models\Tiers\ThirdParty;
use App\Services\Core\GoogleMapsService;
use App\Services\Core\SirenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ThirdPartyService
{
    public function __construct(
        protected SirenService $sirenService,
        protected GoogleMapsService $googleMapsService
    ) {}

    /**
     * Crée un tiers complet à partir d'un SIRET en utilisant l'API SIREN.
     *
     * @throws TiersModuleException
     */
    public function createFromSiret(string $siret, string $type): ?ThirdParty
    {
        try {
            $sirenData = $this->sirenService->getInformation($siret);
        } catch (\Throwable $e) {
            $exception = new TiersModuleException($e->getMessage(), $e->getCode(), $e);
            $exception->notify();
            throw $exception;
        }

        if (! $sirenData) {
            return null;
        }

        try {
            return DB::transaction(function () use ($sirenData, $siret, $type) {
                $uniteLegal = $sirenData['etablissement']['uniteLegal'];
                $address = $sirenData['etablissement']['adresseEtablissement'];

                // 1. Création du tiers
                $thirdParty = ThirdParty::create([
                    'name' => $uniteLegal['denominationUniteLegale'] ?? $uniteLegal['nomUniteLegale'],
                    'legal_name' => $uniteLegale['denominationUniteLegale'] ?? null,
                    'type' => $type,
                    'siren' => substr($siret, 0, 9),
                    'siret' => $siret,
                    'vat_number' => $this->calculateVatNumber(substr($siret, 0, 9)),
                    'last_siren_sync_at' => now(),
                ]);

                // 2. Créatipn de l'adresse
                $this->createAddressFromSiren($thirdParty, $address);

                return $thirdParty;
            });
        } catch (\Throwable $e) {
            Log::critical('Création du tiers par siret impossible: '.$e->getMessage(), (array) $e);
            throw (new TiersModuleException('Création du tiers par siret impossible', 500));
        }
    }

    /**
     * Gère l'ajout d'une nouvelle adresse avec géocodage automatique.
     */
    public function addAddress(ThirdParty $thirdParty, array $data): Address
    {
        try {
            $address = $thirdParty->addresses()->create($data);

            // Le géocodage est déclenché via l'AddressService ou un Observer
            // Mais nous pouvons forcer une mise à jour ici si besoin
            return $address;
        } catch (\Throwable $e) {
            Log::critical('Création de l\'adresse impossible: '.$e->getMessage(), (array) $e);
            $exception = new TiersModuleException('Création de l\'adresse impossible', 500, $e);
            $exception->notify();
            throw $exception;
        }
    }

    /**
     * Calcule la validité financière d'un tiers.
     */
    public function CheckCreditLimit(ThirdParty $thirdParty, float $amount): bool
    {
        // Logique simplifiée pour l'exemple : total des factures impayées vs limite
        // Sera complété lors du développement du module Commerce
        return $thirdParty->credit_limit >= $amount;
    }

    /**
     * Formate et crée l'adresse à partir des données SIREN.
     */
    public function createAddressFromSiren(ThirdParty $thirdParty, array $data): void
    {
        try {
            $street = trim(($data['numeroVoieEtablissement'] ?? '').' '.($data['typeVoieEtablissement'] ?? '').' '.($data['libelleVoieEtablissement'] ?? ''));

            $thirdParty->addresses()->create([
                'type' => AddressType::HEADQUARTERS,
                'street' => $street,
                'zip_code' => $data['codePostalEtablissement'],
                'city' => $data['libelleCommuneEtablissement'],
                'is_default' => true,
            ]);
        } catch (\Throwable $e) {
            Log::critical('Création de l\'adresse par siren impossible: '.$e->getMessage(), (array) $e);
            $exception = new TiersModuleException('Création de l\'adresse par siren impossible', 500);
            $exception->notify();
            throw $exception;
        }
    }

    /**
     * Algorithme simple pour calculer un numéro de TVA intracommunautaire FR.
     */
    public function calculateVatNumber(string $siren): string
    {
        $key = (12 + 3 * ($siren % 97)) % 97;

        return 'FR'.sprintf('%02d', $key).$siren;
    }
}
