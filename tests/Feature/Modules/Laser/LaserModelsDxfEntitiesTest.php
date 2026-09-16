<?php

use App\Models\Laser\LaserDeliveryNoteLine;
use App\Models\Laser\LaserInvoiceLine;
use App\Models\Laser\LaserOrderLine;
use App\Models\Laser\LaserQuoteLine;

it('LaserQuoteLine casts dxf_entities to array', function () {
    $line = new LaserQuoteLine;
    $line->forceFill(['dxf_entities' => ['entities' => [['type' => 'LINE']]]]);

    expect($line->dxf_entities)->toBeArray();
    expect($line->dxf_entities['entities'][0]['type'])->toBe('LINE');
});

it('LaserQuoteLine casts null dxf_entities to null', function () {
    $line = new LaserQuoteLine;
    $line->forceFill(['dxf_entities' => null]);

    expect($line->dxf_entities)->toBeNull();
});

it('LaserOrderLine casts dxf_entities to array', function () {
    $line = new LaserOrderLine;
    $line->forceFill(['dxf_entities' => ['entities' => [['type' => 'CIRCLE']]]]);

    expect($line->dxf_entities)->toBeArray();
    expect($line->dxf_entities['entities'][0]['type'])->toBe('CIRCLE');
});

it('LaserOrderLine casts null dxf_entities to null', function () {
    $line = new LaserOrderLine;
    $line->forceFill(['dxf_entities' => null]);

    expect($line->dxf_entities)->toBeNull();
});

it('LaserInvoiceLine casts dxf_entities to array', function () {
    $line = new LaserInvoiceLine;
    $line->forceFill(['dxf_entities' => ['entities' => [['type' => 'ARC']]]]);

    expect($line->dxf_entities)->toBeArray();
    expect($line->dxf_entities['entities'][0]['type'])->toBe('ARC');
});

it('LaserInvoiceLine casts null dxf_entities to null', function () {
    $line = new LaserInvoiceLine;
    $line->forceFill(['dxf_entities' => null]);

    expect($line->dxf_entities)->toBeNull();
});

it('LaserDeliveryNoteLine casts dxf_entities to array', function () {
    $line = new LaserDeliveryNoteLine;
    $line->forceFill(['dxf_entities' => ['entities' => [['type' => 'LWPOLYLINE']]]]);

    expect($line->dxf_entities)->toBeArray();
    expect($line->dxf_entities['entities'][0]['type'])->toBe('LWPOLYLINE');
});

it('LaserDeliveryNoteLine casts null dxf_entities to null', function () {
    $line = new LaserDeliveryNoteLine;
    $line->forceFill(['dxf_entities' => null]);

    expect($line->dxf_entities)->toBeNull();
});

it('all four models cast empty array correctly', function () {
    $models = [
        new LaserQuoteLine,
        new LaserOrderLine,
        new LaserInvoiceLine,
        new LaserDeliveryNoteLine,
    ];

    foreach ($models as $model) {
        $model->forceFill(['dxf_entities' => []]);
        expect($model->dxf_entities)->toBe([]);
    }
});
