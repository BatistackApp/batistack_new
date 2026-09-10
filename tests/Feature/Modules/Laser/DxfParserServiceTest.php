<?php

use App\Services\Laser\DxfImportResult;
use App\Services\Laser\DxfParserService;

// ============================================================
// DXFImportResult DTO
// ============================================================

it('returns valid result', function () {
    $result = new DxfImportResult(
        lengthMm: 100.0,
        widthMm: 50.0,
        totalCutLengthMm: 300.0,
        entityCount: 5,
        layers: ['CUT', 'ENGRAVE'],
    );

    expect($result->isValid())->toBeTrue()
        ->and($result->error)->toBeNull()
        ->and($result->lengthMm)->toBe(100.0)
        ->and($result->widthMm)->toBe(50.0)
        ->and($result->totalCutLengthMm)->toBe(300.0)
        ->and($result->entityCount)->toBe(5)
        ->and($result->layers)->toBe(['CUT', 'ENGRAVE']);
});

it('returns error result', function () {
    $result = DxfImportResult::error('Invalid file');

    expect($result->isValid())->toBeFalse()
        ->and($result->error)->toBe('Invalid file')
        ->and($result->lengthMm)->toBe(0.0)
        ->and($result->widthMm)->toBe(0.0)
        ->and($result->totalCutLengthMm)->toBe(0.0)
        ->and($result->entityCount)->toBe(0)
        ->and($result->layers)->toBe([]);
});

// ============================================================
// DxfParserService — Empty / Invalid
// ============================================================

it('rejects empty file', function () {
    $parser = app(DxfParserService::class);
    $result = $parser->parse('');

    expect($result->isValid())->toBeFalse()
        ->and($result->error)->toContain('vide');
});

it('rejects whitespace-only file', function () {
    $parser = app(DxfParserService::class);
    $result = $parser->parse("   \n  \n  ");

    expect($result->isValid())->toBeFalse();
});

it('rejects file with no entities', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nHEADER\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeFalse()
        ->and($result->error)->toContain('Aucune entité');
});

// ============================================================
// DxfParserService — LINE entity
// ============================================================

it('parses a single LINE entity', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n0.0\n20\n0.0\n11\n100.0\n21\n50.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(100.0)
        ->and($result->widthMm)->toBe(50.0)
        ->and($result->entityCount)->toBe(1)
        ->and($result->totalCutLengthMm)->toBe(round(sqrt(100 * 100 + 50 * 50), 2))
        ->and($result->layers)->toBe(['CUT']);
});

it('parses multiple LINE entities', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nLAYER1\n10\n0.0\n20\n0.0\n11\n100.0\n21\n0.0\n0\nLINE\n8\nLAYER2\n10\n0.0\n20\n0.0\n11\n0.0\n21\n100.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(100.0)
        ->and($result->widthMm)->toBe(100.0)
        ->and($result->entityCount)->toBe(2)
        ->and($result->totalCutLengthMm)->toBe(200.0)
        ->and($result->layers)->toBe(['LAYER1', 'LAYER2']);
});

// ============================================================
// DxfParserService — LWPOLYLINE entity
// ============================================================

it('parses a closed LWPOLYLINE entity', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLWPOLYLINE\n8\nCUT\n90\n4\n70\n1\n10\n0.0\n20\n0.0\n10\n100.0\n20\n0.0\n10\n100.0\n20\n50.0\n10\n0.0\n20\n50.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(100.0)
        ->and($result->widthMm)->toBe(50.0)
        ->and($result->entityCount)->toBe(1)
        ->and($result->totalCutLengthMm)->toBe(300.0);
});

it('parses open LWPOLYLINE', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLWPOLYLINE\n8\nCUT\n90\n3\n70\n0\n10\n0.0\n20\n0.0\n10\n100.0\n20\n0.0\n10\n100.0\n20\n100.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->totalCutLengthMm)->toBe(200.0);
});

// ============================================================
// DxfParserService — ARC entity
// ============================================================

it('parses an ARC entity', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nARC\n8\nCUT\n10\n0.0\n20\n0.0\n40\n50.0\n50\n0.0\n51\n90.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->entityCount)->toBe(1)
        ->and($result->totalCutLengthMm)->toBe(round(50.0 * M_PI / 2, 2));
});

// ============================================================
// DxfParserService — CIRCLE entity
// ============================================================

it('parses a CIRCLE entity', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nCIRCLE\n8\nCUT\n10\n50.0\n20\n50.0\n40\n25.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->entityCount)->toBe(1)
        ->and($result->lengthMm)->toBe(50.0)
        ->and($result->widthMm)->toBe(50.0)
        ->and($result->totalCutLengthMm)->toBe(round(2 * M_PI * 25.0, 2));
});

// ============================================================
// DxfParserService — Mixed entities
// ============================================================

it('parses mixed entity types', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n0.0\n20\n0.0\n11\n100.0\n21\n0.0\n0\nLWPOLYLINE\n8\nCUT\n90\n2\n70\n0\n10\n0.0\n20\n0.0\n10\n0.0\n20\n50.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->entityCount)->toBe(2)
        ->and($result->totalCutLengthMm)->toBe(150.0);
});

// ============================================================
// DxfParserService — Unsupported entities are skipped
// ============================================================

it('skips unsupported entity types', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nDIMENSION\n8\nDIM\n0\nHATCH\n8\nHATCH\n0\nLINE\n8\nCUT\n10\n0.0\n20\n0.0\n11\n50.0\n21\n50.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->entityCount)->toBe(1);
});

// ============================================================
// DxfParserService — Windows line endings
// ============================================================

it('handles Windows line endings', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\r\nSECTION\r\n2\r\nENTITIES\r\n0\r\nLINE\r\n8\r\nCUT\r\n10\r\n0.0\r\n20\r\n0.0\r\n11\r\n100.0\r\n21\r\n50.0\r\n0\r\nENDSEC\r\n0\r\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(100.0);
});

// ============================================================
// DxfParserService — Real-world DXF structure
// ============================================================

it('handles DXF with HEADER section before ENTITIES', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nHEADER\n9\n\$ACADVER\n1\nAC1015\n0\nENDSEC\n0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n10.0\n20\n20.0\n11\n210.0\n21\n120.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(200.0)
        ->and($result->widthMm)->toBe(100.0)
        ->and($result->entityCount)->toBe(1);
});

// ============================================================
// DxfParserService — Bounding box accuracy
// ============================================================

it('calculates bounding box from negative coordinates', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n-50.0\n20\n-30.0\n11\n50.0\n21\n30.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(100.0)
        ->and($result->widthMm)->toBe(60.0);
});

it('calculates bounding box from multiple entities', function () {
    $parser = app(DxfParserService::class);
    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n0.0\n20\n0.0\n11\n100.0\n21\n50.0\n0\nLINE\n8\nCUT\n10\n-20.0\n20\n-10.0\n11\n200.0\n21\n80.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(220.0)
        ->and($result->widthMm)->toBe(90.0);
});

// ============================================================
// DxfParserService — LWPOLYLINE with bulge (arc segments)
// ============================================================

it('parses LWPOLYLINE with bulge arc segments', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLWPOLYLINE\n8\nCUT\n90\n2\n70\n0\n10\n0.0\n20\n0.0\n42\n1.0\n10\n100.0\n20\n0.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->entityCount)->toBe(1)
        ->and($result->totalCutLengthMm)->toBe(round(M_PI * 50, 2));
});

it('parses LWPOLYLINE with zero bulge (straight segments)', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLWPOLYLINE\n8\nCUT\n90\n3\n70\n0\n10\n0.0\n20\n0.0\n42\n0.0\n10\n100.0\n20\n0.0\n42\n0.0\n10\n100.0\n20\n100.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->totalCutLengthMm)->toBe(200.0);
});

// ============================================================
// DxfParserService — LWPOLYLINE edge cases
// ============================================================

it('handles LWPOLYLINE with single vertex', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLWPOLYLINE\n8\nCUT\n90\n1\n70\n0\n10\n50.0\n20\n50.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->entityCount)->toBe(1)
        ->and($result->totalCutLengthMm)->toBe(0.0);
});

// ============================================================
// DxfParserService — Entity at EOF without ENDSEC
// ============================================================

it('parses entities until EOF without ENDSEC', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n0.0\n20\n0.0\n11\n100.0\n21\n50.0\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->entityCount)->toBe(1)
        ->and($result->lengthMm)->toBe(100.0);
});

// ============================================================
// DxfParserService — ARC bounding box with angles crossing quadrants
// ============================================================

it('calculates bounding box for ARC crossing quadrants', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nARC\n8\nCUT\n10\n0.0\n20\n0.0\n40\n100.0\n50\n45.0\n51\n315.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and(round($result->lengthMm, 2))->toBe(170.71)
        ->and($result->widthMm)->toBe(200.0);
});

it('parses CIRCLE entity with bounding box', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nCIRCLE\n8\nCUT\n10\n100.0\n20\n100.0\n40\n50.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(100.0)
        ->and($result->widthMm)->toBe(100.0);
});

it('calculates bounding box for ARC crossing 0 degrees', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nARC\n8\nCUT\n10\n0.0\n20\n0.0\n40\n100.0\n50\n315.0\n51\n45.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and(round($result->lengthMm, 2))->toBe(29.29)
        ->and(round($result->widthMm, 2))->toBe(141.42);
});

it('calculates bounding box for LWPOLYLINE with bulge', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLWPOLYLINE\n8\nCUT\n90\n2\n70\n0\n10\n0.0\n20\n0.0\n42\n1.0\n10\n100.0\n20\n0.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(100.0)
        ->and($result->widthMm)->toBe(50.0);
});

// ============================================================
// DxfParserService — parseGroupCodes odd lines
// ============================================================

it('handles odd number of lines in DXF content', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLINE\n8\nCUT\n10\n0.0\n20\n0.0\n11\n50.0\n21\n50.0\n0\nENDSEC\n0\nEOF\n";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->entityCount)->toBe(1);
});

it('handles LWPOLYLINE with bulge on coincident points', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\nSECTION\n2\nENTITIES\n0\nLWPOLYLINE\n8\nCUT\n90\n2\n70\n0\n10\n50.0\n20\n50.0\n42\n1.0\n10\n50.0\n20\n50.0\n0\nENDSEC\n0\nEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->entityCount)->toBe(1)
        ->and($result->totalCutLengthMm)->toBe(0.0);
});

it('handles old Mac line endings', function () {
    $parser = app(DxfParserService::class);

    $dxf = "0\rSECTION\r2\rENTITIES\r0\rLINE\r8\rCUT\r10\r0.0\r20\r0.0\r11\r100.0\r21\r50.0\r0\rENDSEC\r0\rEOF";
    $result = $parser->parse($dxf);

    expect($result->isValid())->toBeTrue()
        ->and($result->lengthMm)->toBe(100.0);
});
