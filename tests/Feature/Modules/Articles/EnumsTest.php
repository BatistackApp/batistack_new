<?php

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
