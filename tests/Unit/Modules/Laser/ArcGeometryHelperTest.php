<?php

use App\Services\Laser\ArcGeometryHelper;

beforeEach(function () {
    $this->helper = app(ArcGeometryHelper::class);
});

// ============================================================
// chordLength
// ============================================================

it('calculates chord length for horizontal segment', function () {
    expect($this->helper->chordLength(['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0]))->toBe(100.0);
});

it('calculates chord length for diagonal segment', function () {
    expect($this->helper->chordLength(['x' => 0, 'y' => 0], ['x' => 3, 'y' => 4]))->toBe(5.0);
});

it('returns 0 for coincident points', function () {
    expect($this->helper->chordLength(['x' => 50, 'y' => 50], ['x' => 50, 'y' => 50]))->toBe(0.0);
});

// ============================================================
// radius
// ============================================================

it('calculates radius for semicircle (bulge=1)', function () {
    expect($this->helper->radius(100, 1.0))->toBe(50.0);
});

it('calculates radius for bulge=0.5', function () {
    expect($this->helper->radius(100, 0.5))->toBe(62.5);
});

it('calculates radius for bulge=2.0', function () {
    expect($this->helper->radius(100, 2.0))->toBe(62.5);
});

it('calculates radius for negative bulge', function () {
    expect($this->helper->radius(100, -0.5))->toBe(62.5);
});

// ============================================================
// includedAngle
// ============================================================

it('calculates included angle for semicircle (bulge=1)', function () {
    expect(round($this->helper->includedAngle(1.0), 5))->toBe(round(M_PI, 5));
});

it('calculates included angle for bulge=0.5', function () {
    expect(round($this->helper->includedAngle(0.5), 5))->toBe(round(4 * atan(0.5), 5));
});

it('calculates included angle for bulge=2.0 (major arc)', function () {
    $angle = $this->helper->includedAngle(2.0);
    expect(round($angle, 5))->toBe(round(4 * atan(2.0), 5))
        ->and($angle)->toBeGreaterThan(M_PI);
});

it('calculates included angle for negative bulge', function () {
    expect(round($this->helper->includedAngle(-0.5), 5))->toBe(round(4 * atan(0.5), 5));
});

// ============================================================
// arcLength
// ============================================================

it('calculates arc length for semicircle', function () {
    expect(round($this->helper->arcLength(100, 1.0), 2))->toBe(round(50 * M_PI, 2));
});

it('calculates arc length for bulge=0.5', function () {
    $expected = 62.5 * 4 * atan(0.5);
    expect(round($this->helper->arcLength(100, 0.5), 2))->toBe(round($expected, 2));
});

it('returns 0 for zero chord', function () {
    expect($this->helper->arcLength(0, 1.0))->toBe(0.0);
});

// ============================================================
// center
// ============================================================

it('calculates center for semicircle (bulge=1)', function () {
    $center = $this->helper->center(['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0], 1.0);
    expect(round($center['x'], 4))->toBe(50.0)
        ->and(round($center['y'], 4))->toBe(0.0);
});

it('calculates center for bulge=0.5', function () {
    $center = $this->helper->center(['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0], 0.5);
    expect(round($center['x'], 4))->toBe(50.0)
        ->and(round($center['y'], 4))->toBe(37.5);
});

it('calculates center for bulge=2.0 (major arc)', function () {
    $center = $this->helper->center(['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0], 2.0);
    expect(round($center['x'], 4))->toBe(50.0)
        ->and(round($center['y'], 4))->toBe(-37.5);
});

it('calculates center for bulge=-0.5', function () {
    $center = $this->helper->center(['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0], -0.5);
    expect(round($center['x'], 4))->toBe(50.0)
        ->and(round($center['y'], 4))->toBe(-37.5);
});

it('calculates center for bulge=-2.0 (major arc)', function () {
    $center = $this->helper->center(['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0], -2.0);
    expect(round($center['x'], 4))->toBe(50.0)
        ->and(round($center['y'], 4))->toBe(37.5);
});

it('returns start point for coincident points', function () {
    $center = $this->helper->center(['x' => 50, 'y' => 50], ['x' => 50, 'y' => 50], 1.0);
    expect($center['x'])->toBe(50)
        ->and($center['y'])->toBe(50);
});

// ============================================================
// startAngle
// ============================================================

it('calculates start angle for point at 0 degrees', function () {
    expect(round($this->helper->startAngle(['x' => 100, 'y' => 0], ['x' => 0, 'y' => 0]), 5))->toBe(0.0);
});

it('calculates start angle for point at 90 degrees', function () {
    expect(round($this->helper->startAngle(['x' => 0, 'y' => 100], ['x' => 0, 'y' => 0]), 5))
        ->toBe(round(M_PI / 2, 5));
});

it('calculates start angle for point at 180 degrees', function () {
    expect(round($this->helper->startAngle(['x' => -100, 'y' => 0], ['x' => 0, 'y' => 0]), 5))
        ->toBe(round(M_PI, 5));
});

// ============================================================
// normalizeAngle
// ============================================================

it('normalizes positive angle within range', function () {
    expect(round($this->helper->normalizeAngle(M_PI / 4), 5))->toBe(round(M_PI / 4, 5));
});

it('normalizes negative angle', function () {
    expect(round($this->helper->normalizeAngle(-M_PI / 2), 5))->toBe(round(3 * M_PI / 2, 5));
});

it('normalizes angle greater than 2PI', function () {
    expect(round($this->helper->normalizeAngle(3 * M_PI), 5))->toBe(round(M_PI, 5));
});

it('normalizes zero', function () {
    expect($this->helper->normalizeAngle(0.0))->toBe(0.0);
});

// ============================================================
// isAngleOnArc
// ============================================================

it('detects angle on small CCW arc', function () {
    expect($this->helper->isAngleOnArc(deg2rad(30), deg2rad(60), deg2rad(45), true))->toBeTrue();
});

it('rejects angle outside small CCW arc', function () {
    expect($this->helper->isAngleOnArc(deg2rad(30), deg2rad(60), deg2rad(90), true))->toBeFalse();
});

it('detects angle on arc crossing 0 degrees (CCW)', function () {
    expect($this->helper->isAngleOnArc(deg2rad(315), deg2rad(45), deg2rad(0), true))->toBeTrue();
});

it('rejects angle outside arc crossing 0 degrees (CCW)', function () {
    expect($this->helper->isAngleOnArc(deg2rad(315), deg2rad(45), deg2rad(180), true))->toBeFalse();
});

it('detects angle on CW arc', function () {
    expect($this->helper->isAngleOnArc(deg2rad(60), deg2rad(30), deg2rad(45), false))->toBeTrue();
});

it('rejects angle outside CW arc', function () {
    expect($this->helper->isAngleOnArc(deg2rad(60), deg2rad(30), deg2rad(90), false))->toBeFalse();
});

it('detects angle on CW arc crossing 0 degrees', function () {
    expect($this->helper->isAngleOnArc(deg2rad(45), deg2rad(315), deg2rad(0), false))->toBeTrue();
});

// ============================================================
// svgArcFlags — the critical fix
// ============================================================

it('returns small arc flag for bulge < 1', function () {
    $flags = $this->helper->svgArcFlags(0.5);
    expect($flags['largeArc'])->toBe(0)
        ->and($flags['sweepFlag'])->toBe(0);
});

it('returns large arc flag for bulge > 1 (major arc)', function () {
    $flags = $this->helper->svgArcFlags(2.0);
    expect($flags['largeArc'])->toBe(1)
        ->and($flags['sweepFlag'])->toBe(0);
});

it('returns small arc flag for semicircle (bulge=1)', function () {
    $flags = $this->helper->svgArcFlags(1.0);
    expect($flags['largeArc'])->toBe(0)
        ->and($flags['sweepFlag'])->toBe(0);
});

it('returns sweep flag 1 for negative bulge', function () {
    $flags = $this->helper->svgArcFlags(-0.5);
    expect($flags['largeArc'])->toBe(0)
        ->and($flags['sweepFlag'])->toBe(1);
});

it('returns large arc and sweep flag for negative major bulge', function () {
    $flags = $this->helper->svgArcFlags(-2.0);
    expect($flags['largeArc'])->toBe(1)
        ->and($flags['sweepFlag'])->toBe(1);
});

// ============================================================
// bulgeToSvgArc — SVG output
// ============================================================

it('generates SVG arc for semicircle', function () {
    $svgArc = $this->helper->bulgeToSvgArc(
        ['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0], 1.0,
        1.0, 0, 0, 0, 0
    );
    expect($svgArc)->toMatch('/^A 50(\.0+)? 50(\.0+)? 0 0 0/')
        ->and($svgArc)->toContain('100 0');
});

it('generates SVG arc with large-arc-flag for major arc', function () {
    $svgArc = $this->helper->bulgeToSvgArc(
        ['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0], 2.0,
        1.0, 0, 0, 0, 0
    );
    expect($svgArc)->toContain(' 1 0 ');
});

it('generates SVG arc with sweep-flag 1 for negative bulge', function () {
    $svgArc = $this->helper->bulgeToSvgArc(
        ['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0], -0.5,
        1.0, 0, 0, 0, 0
    );
    expect($svgArc)->toContain(' 0 1 ');
});

it('returns L command for coincident points', function () {
    $svgArc = $this->helper->bulgeToSvgArc(
        ['x' => 50, 'y' => 50], ['x' => 50, 'y' => 50], 1.0,
        1.0, 0, 0, 0, 0
    );
    expect($svgArc)->toMatch('/^L /');
});

it('applies scale and offset to arc output', function () {
    $svgArc = $this->helper->bulgeToSvgArc(
        ['x' => 0, 'y' => 0], ['x' => 100, 'y' => 0], 1.0,
        0.5, 10, 10, 0, 100
    );
    expect($svgArc)->toContain('25')
        ->and($svgArc)->toContain('60 60');
});

// ============================================================
// Geometry consistency: center + radius → arc passes through endpoints
// ============================================================

it('arc with computed center passes through start point', function () {
    $start = ['x' => 0, 'y' => 0];
    $end = ['x' => 100, 'y' => 0];
    $bulge = 0.5;

    $center = $this->helper->center($start, $end, $bulge);
    $radius = $this->helper->radius($this->helper->chordLength($start, $end), $bulge);
    $dist = sqrt(($start['x'] - $center['x']) ** 2 + ($start['y'] - $center['y']) ** 2);

    expect(round($dist, 4))->toBe(round($radius, 4));
});

it('arc with computed center passes through end point', function () {
    $start = ['x' => 0, 'y' => 0];
    $end = ['x' => 100, 'y' => 0];
    $bulge = 0.5;

    $center = $this->helper->center($start, $end, $bulge);
    $radius = $this->helper->radius($this->helper->chordLength($start, $end), $bulge);
    $dist = sqrt(($end['x'] - $center['x']) ** 2 + ($end['y'] - $center['y']) ** 2);

    expect(round($dist, 4))->toBe(round($radius, 4));
});

it('semicircle peak is at expected geometry', function () {
    $start = ['x' => 0, 'y' => 0];
    $end = ['x' => 100, 'y' => 0];

    $center = $this->helper->center($start, $end, 1.0);
    $radius = $this->helper->radius($this->helper->chordLength($start, $end), 1.0);

    expect(round($center['x'], 4))->toBe(50.0)
        ->and(round($center['y'], 4))->toBe(0.0)
        ->and($radius)->toBe(50.0);
});

it('major arc (bulge=2) peak reaches expected y', function () {
    $start = ['x' => 0, 'y' => 0];
    $end = ['x' => 100, 'y' => 0];

    $center = $this->helper->center($start, $end, 2.0);
    $radius = $this->helper->radius($this->helper->chordLength($start, $end), 2.0);

    expect(round($center['y'], 4))->toBe(-37.5)
        ->and(round($center['y'] + $radius, 4))->toBe(25.0);
});
