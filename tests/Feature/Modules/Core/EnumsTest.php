<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Core\UnitType;

it('verifie les valeurs de l\'enum SignatureStatus', function () {
    expect(SignatureStatus::PENDING->value)->toBe('pending')
        ->and(SignatureStatus::SIGNED->value)->toBe('signed')
        ->and(SignatureStatus::REFUSED->value)->toBe('refused')
        ->and(SignatureStatus::EXPIRED->value)->toBe('expired');

    expect(SignatureStatus::PENDING->getLabel())->toBe('En attente')
        ->and(SignatureStatus::SIGNED->getLabel())->toBe('Signé')
        ->and(SignatureStatus::REFUSED->getLabel())->toBe('Refusé')
        ->and(SignatureStatus::EXPIRED->getLabel())->toBe('Expiré');

    expect(SignatureStatus::PENDING->getColor())->toBe('warning')
        ->and(SignatureStatus::SIGNED->getColor())->toBe('success')
        ->and(SignatureStatus::REFUSED->getColor())->toBe('danger')
        ->and(SignatureStatus::EXPIRED->getColor())->toBe('gray');
});

it('verifie les valeurs de l\'enum SignatureType', function () {
    expect(SignatureType::AUTOGRAPH->value)->toBe('autograph')
        ->and(SignatureType::OTP->value)->toBe('otp')
        ->and(SignatureType::CLICK->value)->toBe('click');

    expect(SignatureType::AUTOGRAPH->getLabel())->toBe('Signature manuscrite')
        ->and(SignatureType::OTP->getLabel())->toBe('Validation OTP')
        ->and(SignatureType::CLICK->getLabel())->toBe('Approbation simple');
});

it('verifie les valeurs de l\'enum UnitType', function () {
    expect(UnitType::SURFACE->value)->toBe('surface')
        ->and(UnitType::VOLUME->value)->toBe('volume')
        ->and(UnitType::LENGTH->value)->toBe('length')
        ->and(UnitType::WEIGHT->value)->toBe('weight')
        ->and(UnitType::TIME->value)->toBe('time')
        ->and(UnitType::UNIT->value)->toBe('unit')
        ->and(UnitType::FORFAIT->value)->toBe('forfait');

    expect(UnitType::SURFACE->getLabel())->toBe('Surface')
        ->and(UnitType::VOLUME->getLabel())->toBe('Volume')
        ->and(UnitType::LENGTH->getLabel())->toBe('Longueur')
        ->and(UnitType::WEIGHT->getLabel())->toBe('Poids')
        ->and(UnitType::TIME->getLabel())->toBe('Temps')
        ->and(UnitType::UNIT->getLabel())->toBe('Unité')
        ->and(UnitType::FORFAIT->getLabel())->toBe('Forfait');
});
