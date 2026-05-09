<?php

namespace App\Services\Core;

use App\Models\Core\Company;
use Illuminate\Support\Facades\Cache;

class CompanyService
{
    /**
     * Récupère l'instance unique de l'entreprise avec mise en cache.
     */
    public function getCompany(): ?Company
    {
        return Cache::remember('core_company_singleton', 3600, function () {
            return Company::first();
        });
    }

    /**
     * Récupère les informations pour les en-têtes de documents.
     */
    public function getDocumentHeaderData(): array
    {
        $company = $this->getCompany();

        if (! $company) {
            return [];
        }

        return [
            'name' => $company->legal_name,
            'siret' => $company->siret,
            'vat' => $company->vat_number,
            'address' => "{$company->address}, {$company->zip_code} {$company->city}",
            'logo_url' => $company->getFirstMediaUrl('logo'),
        ];
    }
}
