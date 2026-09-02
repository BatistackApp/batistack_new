<?php

namespace Database\Seeders;

use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\DepreciationMethod;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\FixedAsset;
use Illuminate\Database\Seeder;

class ImmobilisationSeeder extends Seeder
{
    public function run(): void
    {
        if (FixedAsset::count() > 0) {
            return;
        }

        $categories = [
            AssetCategory::create(['name' => 'Matériel de BTP', 'account_code' => '215100', 'compte_amortissement' => '281500', 'default_depreciation_years' => 5, 'default_method' => DepreciationMethod::LINEAR]),
            AssetCategory::create(['name' => 'Outillage', 'account_code' => '215400', 'compte_amortissement' => '281500', 'default_depreciation_years' => 3, 'default_method' => DepreciationMethod::LINEAR]),
            AssetCategory::create(['name' => 'Mobilier de bureau', 'account_code' => '218200', 'compte_amortissement' => '281800', 'default_depreciation_years' => 5, 'default_method' => DepreciationMethod::LINEAR]),
            AssetCategory::create(['name' => 'Matériel informatique', 'account_code' => '218300', 'compte_amortissement' => '281800', 'default_depreciation_years' => 3, 'default_method' => DepreciationMethod::LINEAR]),
            AssetCategory::create(['name' => 'Véhicules', 'account_code' => '215100', 'compte_amortissement' => '281500', 'default_depreciation_years' => 4, 'default_method' => DepreciationMethod::LINEAR]),
        ];

        $assets = [
            ['category' => 0, 'name' => 'Pelleteuse CAT 320', 'price' => 125000, 'life' => 7],
            ['category' => 0, 'name' => 'Chargeuse frontale', 'price' => 85000, 'life' => 7],
            ['category' => 1, 'name' => 'Perceuse filtreuse Hilti', 'price' => 1200, 'life' => 3],
            ['category' => 1, 'name' => 'Meuleuse disque Makita', 'price' => 350, 'life' => 3],
            ['category' => 2, 'name' => 'Bureau direction', 'price' => 2500, 'life' => 5],
            ['category' => 3, 'name' => 'PC Portable DEV', 'price' => 1800, 'life' => 3],
            ['category' => 4, 'name' => 'Renault Master 2023', 'price' => 32000, 'life' => 4],
        ];

        foreach ($assets as $asset) {
            FixedAsset::create([
                'asset_category_id' => $categories[$asset['category']]->id,
                'name' => $asset['name'],
                'serial_number' => strtoupper(uniqid('SN-')),
                'purchase_date' => now()->subMonths(rand(1, 24))->format('Y-m-d'),
                'purchase_price' => $asset['price'],
                'salvage_value' => 0,
                'depreciation_method' => DepreciationMethod::LINEAR,
                'useful_life_years' => $asset['life'],
                'status' => AssetStatus::ACTIVE,
            ]);
        }
    }
}
