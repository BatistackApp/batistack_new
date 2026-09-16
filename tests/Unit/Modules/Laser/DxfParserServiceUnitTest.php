<?php

use App\Services\Laser\DxfParserService;

it('returns error for completely malformed content', function () {
    $dxf = new DxfParserService;
    $result = $dxf->parse("this is not a DXF file at all\njust random text\n");

    expect($result->entityCount)->toBe(0);
});

it('returns entities field in DxfImportResult', function () {
    $content = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n0\n20\n0\n11\n100\n21\n100\n0\nENDSEC\n0\nEOF\n";
    $dxf = new DxfParserService;
    $result = $dxf->parse($content);

    expect($result->entities)->toHaveCount(1);
    expect($result->entities[0]['type'])->toBe('LINE');
    expect($result->entities[0]['layer'])->toBe('CUT');
});

it('returns entities with all field types populated', function () {
    $content = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n5\n20\n10\n11\n50\n21\n60\n0\nENDSEC\n0\nEOF\n";
    $dxf = new DxfParserService;
    $result = $dxf->parse($content);

    expect($result->entities[0]['data']['start_x'])->toBe(5.0);
    expect($result->entities[0]['data']['start_y'])->toBe(10.0);
    expect($result->entities[0]['data']['end_x'])->toBe(50.0);
    expect($result->entities[0]['data']['end_y'])->toBe(60.0);
});

it('handles POLYLINE with legacy VERTEX and SEQEND', function () {
    $content = "0\nSECTION\n2\nENTITIES\n"
        ."0\nPOLYLINE\n8\nCUT\n90\n3\n70\n1\n"
        ."0\nVERTEX\n10\n0\n20\n0\n"
        ."0\nVERTEX\n10\n100\n20\n0\n42\n1\n"
        ."0\nVERTEX\n10\n100\n20\n100\n"
        ."0\nSEQEND\n"
        ."0\nENDSEC\n0\nEOF\n";

    $dxf = new DxfParserService;
    $result = $dxf->parse($content);

    expect($result->entities)->toHaveCount(1);
    expect($result->entities[0]['type'])->toBe('LWPOLYLINE');
    expect($result->entities[0]['data']['vertices'])->toHaveCount(3);
});

it('handles missing layer in entity defaults to 0', function () {
    $content = "0\nSECTION\n2\nENTITIES\n0\nLINE\n10\n0\n20\n0\n11\n10\n21\n10\n0\nENDSEC\n0\nEOF\n";
    $dxf = new DxfParserService;
    $result = $dxf->parse($content);

    expect($result->entities[0]['layer'])->toBe('0');
});

it('handles LWPOLYLINE without legacy flag', function () {
    $content = "0\nSECTION\n2\nENTITIES\n"
        ."0\nLWPOLYLINE\n8\nCUT\n90\n2\n70\n0\n"
        ."10\n0\n20\n0\n"
        ."10\n100\n20\n100\n"
        ."0\nENDSEC\n0\nEOF\n";

    $dxf = new DxfParserService;
    $result = $dxf->parse($content);

    expect($result->entities)->toHaveCount(1);
    expect($result->entities[0]['type'])->toBe('LWPOLYLINE');
    expect($result->entities[0]['data']['vertices'])->toHaveCount(2);
});

it('parses LWPOLYLINE with bulge on vertices', function () {
    $content = "0\nSECTION\n2\nENTITIES\n"
        ."0\nLWPOLYLINE\n8\nCUT\n90\n3\n70\n0\n"
        ."10\n0\n20\n0\n42\n0.5\n"
        ."10\n100\n20\n0\n"
        ."10\n100\n20\n100\n"
        ."0\nENDSEC\n0\nEOF\n";

    $dxf = new DxfParserService;
    $result = $dxf->parse($content);

    expect($result->entities[0]['data']['vertices'][0]['bulge'])->toBe(0.5);
    expect($result->entities[0]['data']['vertices'][1]['bulge'])->toBe(0.0);
});

it('parses ARC entity with angles', function () {
    $content = "0\nSECTION\n2\nENTITIES\n"
        ."0\nARC\n8\nCUT\n10\n50\n20\n50\n40\n30\n50\n0\n51\n90\n"
        ."0\nENDSEC\n0\nEOF\n";

    $dxf = new DxfParserService;
    $result = $dxf->parse($content);

    expect($result->entities[0]['data']['radius'])->toBe(30.0);
    expect($result->entities[0]['data']['start_angle'])->toBe(0.0);
    expect($result->entities[0]['data']['end_angle'])->toBe(90.0);
});

it('calculates arc length correctly for quarter circle', function () {
    $content = "0\nSECTION\n2\nENTITIES\n"
        ."0\nARC\n8\nCUT\n10\n0\n20\n0\n40\n100\n50\n0\n51\n90\n"
        ."0\nENDSEC\n0\nEOF\n";

    $dxf = new DxfParserService;
    $result = $dxf->parse($content);

    // quarter circle: π/2 * 100 = 157.08
    expect(round($result->totalCutLengthMm, 1))->toBe(157.1);
});

it('calculates circle length correctly', function () {
    $content = "0\nSECTION\n2\nENTITIES\n"
        ."0\nCIRCLE\n8\nCUT\n10\n0\n20\n0\n40\n50\n"
        ."0\nENDSEC\n0\nEOF\n";

    $dxf = new DxfParserService;
    $result = $dxf->parse($content);

    // circumference: 2π * 50 = 314.16
    expect(round($result->totalCutLengthMm, 1))->toBe(314.2);
});

it('returns error when allowed layers filter out all entities', function () {
    $content = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n0\n20\n0\n11\n10\n21\n10\n0\nENDSEC\n0\nEOF\n";
    $dxf = new DxfParserService;
    $result = $dxf->parse($content, ['ALLOWED_LAYER']);

    expect($result->entityCount)->toBe(0);
    expect($result->error)->toContain('Aucune entité de découpe');
});
