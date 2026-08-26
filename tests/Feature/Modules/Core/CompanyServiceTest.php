<?php

use App\Models\Core\Company;
use App\Services\Core\CompanyService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->service = new CompanyService;
    Cache::flush();
});

describe('CompanyService', function () {
    test('getDocumentHeaderData returns empty array if no company exists', function () {
        Company::query()->delete();

        $data = $this->service->getDocumentHeaderData();

        expect($data)->toBeArray()->toBeEmpty();
    });

    test('getDocumentHeaderData returns formatted data when company exists', function () {
        Company::query()->delete();
        $company = Company::factory()->create([
            'legal_name' => 'Batistack SAS',
            'siret' => '12345678901234',
            'vat_number' => 'FR12123456789',
            'address' => '10 rue de la Paix',
            'zip_code' => '75000',
            'city' => 'Paris',
        ]);

        $data = $this->service->getDocumentHeaderData();

        expect($data)->toBeArray()
            ->toHaveKey('name', 'Batistack SAS')
            ->toHaveKey('siret', '12345678901234')
            ->toHaveKey('vat', 'FR12123456789')
            ->toHaveKey('address', '10 rue de la Paix, 75000 Paris')
            ->toHaveKey('logo_url');
    });
});
