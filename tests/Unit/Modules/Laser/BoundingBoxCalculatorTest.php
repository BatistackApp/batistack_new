<?php

use App\Services\Laser\ArcGeometryHelper;
use App\Services\Laser\BoundingBoxCalculator;

beforeEach(function () {
    $this->calc = new BoundingBoxCalculator(new ArcGeometryHelper);
});

it('returns zero bounds for empty entities', function () {
    $result = $this->calc->calculate([]);

    expect($result)->toBe([
        'min_x' => 0,
        'max_x' => 0,
        'min_y' => 0,
        'max_y' => 0,
    ]);
});

it('calculates bounding box for a single LINE', function () {
    $entities = [
        [
            'type' => 'LINE',
            'layer' => '0',
            'data' => ['start_x' => 10, 'start_y' => 20, 'end_x' => 50, 'end_y' => 80],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(10.0);
    expect($result['max_x'])->toBe(50.0);
    expect($result['min_y'])->toBe(20.0);
    expect($result['max_y'])->toBe(80.0);
});

it('calculates bounding box for multiple LINEs', function () {
    $entities = [
        [
            'type' => 'LINE',
            'layer' => '0',
            'data' => ['start_x' => -10, 'start_y' => -20, 'end_x' => 5, 'end_y' => 15],
        ],
        [
            'type' => 'LINE',
            'layer' => '0',
            'data' => ['start_x' => 0, 'start_y' => 0, 'end_x' => 100, 'end_y' => 200],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(-10.0);
    expect($result['max_x'])->toBe(100.0);
    expect($result['min_y'])->toBe(-20.0);
    expect($result['max_y'])->toBe(200.0);
});

it('uses defaults 0 for missing LINE coordinates', function () {
    $entities = [
        [
            'type' => 'LINE',
            'layer' => '0',
            'data' => [],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(0.0);
    expect($result['max_x'])->toBe(0.0);
    expect($result['min_y'])->toBe(0.0);
    expect($result['max_y'])->toBe(0.0);
});

it('calculates bounding box for a CIRCLE', function () {
    $entities = [
        [
            'type' => 'CIRCLE',
            'layer' => '0',
            'data' => ['start_x' => 50, 'start_y' => 50, 'radius' => 25],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(25.0);
    expect($result['max_x'])->toBe(75.0);
    expect($result['min_y'])->toBe(25.0);
    expect($result['max_y'])->toBe(75.0);
});

it('calculates bounding box for a CIRCLE at origin with radius 0', function () {
    $entities = [
        [
            'type' => 'CIRCLE',
            'layer' => '0',
            'data' => ['start_x' => 0, 'start_y' => 0, 'radius' => 0],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(0.0);
    expect($result['max_x'])->toBe(0.0);
    expect($result['min_y'])->toBe(0.0);
    expect($result['max_y'])->toBe(0.0);
});

it('calculates bounding box for a CIRCLE with negative center', function () {
    $entities = [
        [
            'type' => 'CIRCLE',
            'layer' => '0',
            'data' => ['start_x' => -100, 'start_y' => -100, 'radius' => 10],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(-110.0);
    expect($result['max_x'])->toBe(-90.0);
    expect($result['min_y'])->toBe(-110.0);
    expect($result['max_y'])->toBe(-90.0);
});

it('calculates bounding box for an ARC', function () {
    // ARC from 0° to 90° (quarter circle), center at 0,0, radius 50
    $entities = [
        [
            'type' => 'ARC',
            'layer' => '0',
            'data' => [
                'start_x' => 0,
                'start_y' => 0,
                'radius' => 50,
                'start_angle' => 0,
                'end_angle' => 90,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    // ARC endpoints: (50, 0) and (0, 50)
    expect(round($result['min_x'], 2))->toBe(0.0);
    expect(round($result['max_x'], 2))->toBe(50.0);
    expect(round($result['min_y'], 2))->toBe(0.0);
    expect(round($result['max_y'], 2))->toBe(50.0);
});

it('includes cardinal extrema when ARC spans them', function () {
    // ARC from 45° to 135° — spans 90° (pi/2) which hits the top of the circle
    $entities = [
        [
            'type' => 'ARC',
            'layer' => '0',
            'data' => [
                'start_x' => 0,
                'start_y' => 0,
                'radius' => 100,
                'start_angle' => 45,
                'end_angle' => 135,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    // 45°: (70.71, 70.71), 135°: (-70.71, 70.71)
    // 90° should be included → max_y = 100
    expect($result['max_y'])->toBe(100.0);
    expect(round($result['min_x'], 2))->toBe(-70.71);
    expect(round($result['max_x'], 2))->toBe(70.71);
});

it('calculates bounding box for open LWPOLYLINE without bulge', function () {
    $entities = [
        [
            'type' => 'LWPOLYLINE',
            'layer' => '0',
            'data' => [
                'vertices' => [
                    ['x' => 0, 'y' => 0, 'bulge' => 0],
                    ['x' => 100, 'y' => 0, 'bulge' => 0],
                    ['x' => 100, 'y' => 50, 'bulge' => 0],
                ],
                'flags' => 0,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(0.0);
    expect($result['max_x'])->toBe(100.0);
    expect($result['min_y'])->toBe(0.0);
    expect($result['max_y'])->toBe(50.0);
});

it('calculates bounding box for closed LWPOLYLINE (includes closing segment)', function () {
    $entities = [
        [
            'type' => 'LWPOLYLINE',
            'layer' => '0',
            'data' => [
                'vertices' => [
                    ['x' => 0, 'y' => 0, 'bulge' => 0],
                    ['x' => 100, 'y' => 0, 'bulge' => 0],
                    ['x' => 100, 'y' => 100, 'bulge' => 0],
                ],
                'flags' => 1,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(0.0);
    expect($result['max_x'])->toBe(100.0);
    expect($result['min_y'])->toBe(0.0);
    expect($result['max_y'])->toBe(100.0);
});

it('returns zero bounds for LWPOLYLINE with fewer than 2 vertices', function () {
    $entities = [
        [
            'type' => 'LWPOLYLINE',
            'layer' => '0',
            'data' => [
                'vertices' => [
                    ['x' => 50, 'y' => 50, 'bulge' => 0],
                ],
                'flags' => 0,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(50.0);
    expect($result['max_x'])->toBe(50.0);
    expect($result['min_y'])->toBe(50.0);
    expect($result['max_y'])->toBe(50.0);
});

it('accounts for bulge arc extrema beyond endpoints', function () {
    // Semicircle bulge (bulge=1) from (0,0) to (100,0) → arc goes up to y=50
    $entities = [
        [
            'type' => 'LWPOLYLINE',
            'layer' => '0',
            'data' => [
                'vertices' => [
                    ['x' => 0, 'y' => 0, 'bulge' => 1.0],
                    ['x' => 100, 'y' => 0, 'bulge' => 0],
                ],
                'flags' => 0,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(0.0);
    expect($result['max_x'])->toBe(100.0);
    expect($result['min_y'])->toBe(0.0);
    expect($result['max_y'])->toBeGreaterThanOrEqual(49.0);
});

it('accounts for negative bulge arc extrema', function () {
    // Negative bulge from (0,0) to (100,0) → arc curves downward
    $entities = [
        [
            'type' => 'LWPOLYLINE',
            'layer' => '0',
            'data' => [
                'vertices' => [
                    ['x' => 0, 'y' => 0, 'bulge' => -1.0],
                    ['x' => 100, 'y' => 0, 'bulge' => 0],
                ],
                'flags' => 0,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_y'])->toBeLessThanOrEqual(-49.0);
});

it('handles coincident points in bulge arc (zero chord)', function () {
    $entities = [
        [
            'type' => 'LWPOLYLINE',
            'layer' => '0',
            'data' => [
                'vertices' => [
                    ['x' => 50, 'y' => 50, 'bulge' => 1.0],
                    ['x' => 50, 'y' => 50, 'bulge' => 0],
                ],
                'flags' => 0,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(50.0);
    expect($result['max_x'])->toBe(50.0);
});

it('skips unknown entity types gracefully', function () {
    $entities = [
        [
            'type' => 'DIMENSION',
            'layer' => '0',
            'data' => [],
        ],
        [
            'type' => 'HATCH',
            'layer' => '0',
            'data' => [],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result)->toBe([
        'min_x' => 0,
        'max_x' => 0,
        'min_y' => 0,
        'max_y' => 0,
    ]);
});

it('calculates combined bounding box for mixed entity types', function () {
    $entities = [
        [
            'type' => 'LINE',
            'layer' => '0',
            'data' => ['start_x' => 0, 'start_y' => 0, 'end_x' => 10, 'end_y' => 10],
        ],
        [
            'type' => 'CIRCLE',
            'layer' => '0',
            'data' => ['start_x' => 100, 'start_y' => 100, 'radius' => 10],
        ],
        [
            'type' => 'ARC',
            'layer' => '0',
            'data' => ['start_x' => 0, 'start_y' => 0, 'radius' => 30, 'start_angle' => 0, 'end_angle' => 270],
        ],
    ];

    $result = $this->calc->calculate($entities);

    // ARC from 0° to 270° → endpoints at (30,0) and (0,-30), cardinal angles 90°→(0,30), 180°→(-30,0)
    expect($result['min_x'])->toBe(-30.0);
    expect($result['max_x'])->toBe(110.0);
    expect($result['min_y'])->toBe(-30.0);
    expect($result['max_y'])->toBe(110.0);
});

it('delegates normalizeAngle to ArcGeometryHelper', function () {
    $result = $this->calc->normalizeAngle(2 * M_PI + 0.5);

    expect($result)->toBe(0.5);
});

it('delegates isAngleOnArc to ArcGeometryHelper', function () {
    $result = $this->calc->isAngleOnArc(0, M_PI / 2, M_PI / 4);

    expect($result)->toBeTrue();
});

it('handles ARC spanning nearly full circle (1° to 359°)', function () {
    $entities = [
        [
            'type' => 'ARC',
            'layer' => '0',
            'data' => [
                'start_x' => 10,
                'start_y' => 20,
                'radius' => 50,
                'start_angle' => 1,
                'end_angle' => 359,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    // ARC from 1° to 359° spans most cardinal angles (0°→(60,20), 90°→(10,70), 270°→(10,-30))
    expect(round($result['min_x'], 0))->toBe(-40.0);
    expect(round($result['max_x'], 0))->toBe(60.0);
    expect(round($result['min_y'], 0))->toBe(-30.0);
    expect(round($result['max_y'], 0))->toBe(70.0);
});

it('handles ARC that crosses 0 degrees (negative sweep)', function () {
    // Start at 315°, end at 45° → sweep = 90° crossing 0°
    $entities = [
        [
            'type' => 'ARC',
            'layer' => '0',
            'data' => [
                'start_x' => 0,
                'start_y' => 0,
                'radius' => 100,
                'start_angle' => 315,
                'end_angle' => 45,
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    // 315° → (70.71, -70.71), 45° → (70.71, 70.71), 0° → (100, 0) included
    expect($result['max_x'])->toBe(100.0);
});

it('handles LWPOLYLINE with default empty flags', function () {
    $entities = [
        [
            'type' => 'LWPOLYLINE',
            'layer' => '0',
            'data' => [
                'vertices' => [
                    ['x' => 0, 'y' => 0],
                    ['x' => 10, 'y' => 10],
                ],
            ],
        ],
    ];

    $result = $this->calc->calculate($entities);

    expect($result['min_x'])->toBe(0.0);
    expect($result['max_x'])->toBe(10.0);
});
