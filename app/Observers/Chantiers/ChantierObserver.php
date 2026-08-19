<?php

namespace App\Observers\Chantiers;

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Locations\InternalRentalInvoiceStatus;
use App\Jobs\Chantiers\GeocodeChantierAddressJob;
use App\Jobs\Chantiers\InitializeChantierPhasesJob;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\FixedAsset;
use Illuminate\Support\Str;

class ChantierObserver
{
    public function creating(Chantier $chantier): void
    {
        if (empty($chantier->uuid)) {
            $chantier->uuid = (string) Str::uuid();
        }
    }

    public function created(Chantier $chantier): void
    {
        InitializeChantierPhasesJob::dispatch($chantier);
        GeocodeChantierAddressJob::dispatch($chantier);
    }

    public function saved(Chantier $chantier): void
    {
        // On vérifie si l'un des composants de l'adresse a changé ou si c'est une création
        if ($chantier->wasRecentlyCreated || $chantier->wasChanged(['address', 'zip_code', 'city'])) {
            // Le job est dispatché vers la file d'attente pour ne pas ralentir l'UI
            GeocodeChantierAddressJob::dispatch($chantier);
        }
    }

    /**
     * Lorsqu'un chantier passe à "FINISHED", on annule les facturations internes
     * des immobilisations encore rattachées afin de ne pas impacter son budget analytique.
     */
    public function updated(Chantier $chantier): void
    {
        if ($chantier->wasChanged('status') && $chantier->status === ChantierStatus::FINISHED) {
            $this->cancelInternalRentalInvoices($chantier);
            $this->releaseFixedAssets($chantier);
        }
    }

    protected function cancelInternalRentalInvoices(Chantier $chantier): void
    {
        $chantier->internalRentalInvoices()
            ->where('status', '!=', InternalRentalInvoiceStatus::CANCELED->value)
            ->update(['status' => InternalRentalInvoiceStatus::CANCELED->value]);
    }

    /**
     * Libère les immobilisations encore affectées au chantier terminé et ferme
     * leur trace d'affectation (la facturation interne est déjà annulée).
     */
    protected function releaseFixedAssets(Chantier $chantier): void
    {
        FixedAsset::query()
            ->where('chantier_id', $chantier->id)
            ->get()
            ->each(function (FixedAsset $asset) {
                $asset->release_reason = 'Chantier terminé';
                $asset->update(['chantier_id' => null]);
            });
    }
}
