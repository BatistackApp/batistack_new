<?php

use App\Enums\Articles\GhsPictogram;
use App\Enums\Articles\HazardCategory;
use App\Enums\Articles\ItemType;
use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;

it('verifie les valeurs de l\'enum ItemType', function () {
    expect(ItemType::STOCKABLE->value)->toBe('stockable')
        ->and(ItemType::CONSUMABLE->value)->toBe('consumable')
        ->and(ItemType::LABOR->value)->toBe('labor')
        ->and(ItemType::WORK->value)->toBe('work');

    expect(ItemType::STOCKABLE->getLabel())->toBe('Matériel Stockable')
        ->and(ItemType::CONSUMABLE->getLabel())->toBe('Consommable')
        ->and(ItemType::LABOR->getLabel())->toBe('Main d\'œuvre')
        ->and(ItemType::WORK->getLabel())->toBe('Ouvrage / Recette');

    expect(ItemType::STOCKABLE->getColor())->toBe('success')
        ->and(ItemType::CONSUMABLE->getColor())->toBe('warning')
        ->and(ItemType::LABOR->getColor())->toBe('info')
        ->and(ItemType::WORK->getColor())->toBe('primary');
});

it('verifie les valeurs de l\'enum StockMouvementSource', function () {
    expect(StockMouvementSource::PURCHASE->value)->toBe('purchase')
        ->and(StockMouvementSource::SITE->value)->toBe('site')
        ->and(StockMouvementSource::INVENTORY->value)->toBe('inventory')
        ->and(StockMouvementSource::INTERNAL->value)->toBe('internal')
        ->and(StockMouvementSource::RETURN->value)->toBe('return');

    expect(StockMouvementSource::PURCHASE->getLabel())->toBe('Commande Fournisseur')
        ->and(StockMouvementSource::SITE->getLabel())->toBe('Consommation Chantier')
        ->and(StockMouvementSource::INVENTORY->getLabel())->toBe('Régularisation Inventaire')
        ->and(StockMouvementSource::INTERNAL->getLabel())->toBe('Usage Interne / Perte')
        ->and(StockMouvementSource::RETURN->getLabel())->toBe('Retour Chantier');
});

it('verifie les valeurs de l\'enum StockMouvementType', function () {
    expect(StockMouvementType::IN->value)->toBe('in')
        ->and(StockMouvementType::OUT->value)->toBe('out')
        ->and(StockMouvementType::TRANSFER->value)->toBe('transfer')
        ->and(StockMouvementType::ADJUSTMENT->value)->toBe('adjustment');

    expect(StockMouvementType::IN->getLabel())->toBe('Entrée')
        ->and(StockMouvementType::OUT->getLabel())->toBe('Sortie')
        ->and(StockMouvementType::TRANSFER->getLabel())->toBe('Transfert')
        ->and(StockMouvementType::ADJUSTMENT->getLabel())->toBe('Ajustement');

    expect(StockMouvementType::IN->getColor())->toBe('success')
        ->and(StockMouvementType::OUT->getColor())->toBe('danger')
        ->and(StockMouvementType::TRANSFER->getColor())->toBe('info')
        ->and(StockMouvementType::ADJUSTMENT->getColor())->toBe('warning');
});

it('verifie les valeurs de lenum GhsPictogram', function () {
    expect(GhsPictogram::GHS01->value)->toBe('ghs01')
        ->and(GhsPictogram::GHS02->value)->toBe('ghs02')
        ->and(GhsPictogram::GHS03->value)->toBe('ghs03')
        ->and(GhsPictogram::GHS04->value)->toBe('ghs04')
        ->and(GhsPictogram::GHS05->value)->toBe('ghs05')
        ->and(GhsPictogram::GHS06->value)->toBe('ghs06')
        ->and(GhsPictogram::GHS07->value)->toBe('ghs07')
        ->and(GhsPictogram::GHS08->value)->toBe('ghs08')
        ->and(GhsPictogram::GHS09->value)->toBe('ghs09');

    expect(GhsPictogram::GHS01->getLabel())->toBe('Explosif (GHS01)')
        ->and(GhsPictogram::GHS02->getLabel())->toBe('Inflammable (GHS02)')
        ->and(GhsPictogram::GHS05->getLabel())->toBe('Corrosif (GHS05)')
        ->and(GhsPictogram::GHS09->getLabel())->toBe('Dangereux pour l\'environnement (GHS09)');

    foreach (GhsPictogram::cases() as $pictogram) {
        expect($pictogram->getGlyph())->not->toBeEmpty();
    }

    expect(GhsPictogram::GHS01->getBadgeColor())->toBe('danger')
        ->and(GhsPictogram::GHS04->getBadgeColor())->toBe('warning')
        ->and(GhsPictogram::GHS07->getBadgeColor())->toBe('info')
        ->and(GhsPictogram::GHS09->getBadgeColor())->toBe('success');
});

it('verifie les valeurs de lenum HazardCategory', function () {
    expect(HazardCategory::EXPLOSIVE->value)->toBe('explosive')
        ->and(HazardCategory::ENVIRONMENTALLY_HAZARDOUS->value)->toBe('environmentally_hazardous');

    expect(HazardCategory::EXPLOSIVE->getLabel())->toBe('Explosif')
        ->and(HazardCategory::OXIDIZING->getLabel())->toBe('Comburant')
        ->and(HazardCategory::HARMFUL->getLabel())->toBe('Nocif / Irritant')
        ->and(HazardCategory::CARCINOGENIC->getLabel())->toBe('Cancérogène')
        ->and(HazardCategory::ENVIRONMENTALLY_HAZARDOUS->getLabel())->toBe('Dangereux pour l\'environnement');
});
