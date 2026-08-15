<?php

use App\Enums\Securite\RiskType;

it('fournit un label pour chaque type de risque', function () {
    foreach (RiskType::cases() as $risk) {
        expect($risk->getLabel())->not->toBeNull()->not->toBe('');
    }
});

it('recommande des EPI pour les risques disposant d\'équipements', function () {
    foreach (RiskType::cases() as $risk) {
        if ($risk === RiskType::EXPLOSION) {
            continue;
        }

        expect($risk->getEpi())->not->toBeEmpty();
    }

    expect(RiskType::EXPLOSION->getEpi())->toBeEmpty();
});

it('fournit des mesures collectives pour chaque type de risque', function () {
    foreach (RiskType::cases() as $risk) {
        expect($risk->getCollective())->not->toBeEmpty();
    }
});

it('classe l\'interdiction des étincelles comme mesure organisationnelle', function () {
    expect(RiskType::EXPLOSION->getCollective())
        ->toContain('Interdiction de tout objet ou outil produisant des étincelles')
        ->and(RiskType::EXPLOSION->getEpi())
        ->not->toContain('Interdiction de tout objet ou outil produisant des étincelles');
});

it('expose les libellés clés du risque', function () {
    expect(RiskType::EXPLOSION->getLabel())->toBe('Risque d\'explosion')
        ->and(RiskType::INTOXICATION->getLabel())->toBe('Risque d\'intoxication / nocif')
        ->and(RiskType::POLLUTION->getLabel())->toBe('Risque de pollution de l\'environnement');
});
