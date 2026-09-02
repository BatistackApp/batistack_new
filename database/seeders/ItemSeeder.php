<?php

namespace Database\Seeders;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use App\Models\Articles\Warehouse;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        if (Warehouse::count() === 0) {
            Warehouse::create(['name' => 'Dépôt Principal', 'location' => 'Zone Industrielle Nord']);
        }

        $unitH = Unit::where('symbol', 'h')->first();
        $unitM2 = Unit::where('symbol', 'm²')->first();
        $unitU = Unit::where('symbol', 'u')->first();
        $vat20 = VatRate::where('rate', 20)->first();

        // 1. Création de Main d'œuvre
        $moPose = Item::firstOrCreate(
            ['reference' => 'MO-COUV-01'],
            [
                'name' => 'Pose et Installation de couverture',
                'description' => 'Heure de main d\'œuvre qualifiée pour pose de toiture',
                'type' => ItemType::LABOR,
                'unit_id' => $unitH->id,
                'vat_rate_id' => $vat20->id,
                'purchase_price' => 35.0000,
                'selling_price' => 55.0000,
            ]
        );

        // 2. Création de Matériel
        $tuile = Item::firstOrCreate(
            ['reference' => 'MAT-TUILE-RED'],
            [
                'name' => 'Tuile Romane Rouge',
                'type' => ItemType::STOCKABLE,
                'unit_id' => $unitU->id,
                'vat_rate_id' => $vat20->id,
                'purchase_price' => 1.2000,
                'selling_price' => 2.5000,
            ]
        );

        // 3. Création d'un Ouvrage (M² de toiture)
        $ouvrage = Item::firstOrCreate(
            ['reference' => 'OUV-TOIT-01'],
            [
                'name' => 'Ouvrage : Toiture Tuile Romane',
                'description' => 'Fourniture et pose au m²',
                'type' => ItemType::WORK,
                'unit_id' => $unitM2->id,
                'vat_rate_id' => $vat20->id,
                'purchase_price' => 0,
                'selling_price' => 85.0000,
            ]
        );

        // Composition de l'ouvrage
        if (! $ouvrage->components()->exists()) {
            $ouvrage->components()->createMany([
                ['child_item_id' => $tuile->id, 'quantity' => 13],
                ['child_item_id' => $moPose->id, 'quantity' => 0.75],
            ]);
        }
    }
}
