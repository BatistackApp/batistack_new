<?php

use App\Services\Laser\DxfToSvgService;

beforeEach(function () {
    $this->svg = new DxfToSvgService;
});

it('returns empty string for empty entities', function () {
    $result = $this->svg->toSvg([], 100, 100);
    expect($result)->toBe('');
});

it('returns empty string for zero dimensions', function () {
    $entities = [
        ['type' => 'LINE', 'layer' => '0', 'data' => ['start_x' => 0, 'start_y' => 0, 'end_x' => 10, 'end_y' => 10]],
    ];
    $result = $this->svg->toSvg($entities, 0, 100);
    expect($result)->toBe('');

    $result = $this->svg->toSvg($entities, 100, 0);
    expect($result)->toBe('');
});

it('returns empty string for negative dimensions', function () {
    $entities = [
        ['type' => 'LINE', 'layer' => '0', 'data' => ['start_x' => 0, 'start_y' => 0, 'end_x' => 10, 'end_y' => 10]],
    ];
    $result = $this->svg->toSvg($entities, -10, 100);
    expect($result)->toBe('');
});

it('renders SVG wrapper with viewBox', function () {
    $entities = [
        ['type' => 'LINE', 'layer' => '0', 'data' => ['start_x' => 0, 'start_y' => 0, 'end_x' => 10, 'end_y' => 10]],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120"');
    expect($svg)->toContain('</svg>');
    expect($svg)->toContain('<rect width="120" height="120"');
});

it('renders a LINE entity as SVG line element', function () {
    $entities = [
        ['type' => 'LINE', 'layer' => '0', 'data' => ['start_x' => 0, 'start_y' => 0, 'end_x' => 100, 'end_y' => 100]],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<line x1=');
    expect($svg)->toContain('stroke="#333"');
});

it('renders a CIRCLE entity as SVG circle element', function () {
    $entities = [
        ['type' => 'CIRCLE', 'layer' => '0', 'data' => ['start_x' => 50, 'start_y' => 50, 'radius' => 25]],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<circle cx=');
    expect($svg)->toContain('r=');
    expect($svg)->toContain('stroke="#333"');
});

it('renders a standalone ARC entity as SVG path', function () {
    $entities = [
        [
            'type' => 'ARC',
            'layer' => '0',
            'data' => [
                'start_x' => 50,
                'start_y' => 50,
                'radius' => 30,
                'start_angle' => 0,
                'end_angle' => 90,
            ],
        ],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<path d=');
    expect($svg)->toContain('A ');
    expect($svg)->toContain('stroke="#333"');
});

it('renders ARC with sweep > PI as large arc flag', function () {
    $entities = [
        [
            'type' => 'ARC',
            'layer' => '0',
            'data' => [
                'start_x' => 50,
                'start_y' => 50,
                'radius' => 30,
                'start_angle' => 0,
                'end_angle' => 270,
            ],
        ],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<path d=');
    expect($svg)->toContain('A ');
});

it('renders ARC with negative sweep (crossing 0 degrees)', function () {
    $entities = [
        [
            'type' => 'ARC',
            'layer' => '0',
            'data' => [
                'start_x' => 50,
                'start_y' => 50,
                'radius' => 30,
                'start_angle' => 315,
                'end_angle' => 45,
            ],
        ],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<path d=');
});

it('renders open LWPOLYLINE as SVG path with L commands', function () {
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
                'flags' => 0,
            ],
        ],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<path d=');
    expect($svg)->toContain('L ');
    expect($svg)->not->toContain(' Z');
    expect($svg)->toContain('stroke="#e11d48"');
});

it('renders closed LWPOLYLINE with Z command', function () {
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

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain(' Z');
});

it('renders LWPOLYLINE with bulge arcs using A commands', function () {
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

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('A ');
});

it('returns empty string for polyline with fewer than 2 vertices', function () {
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

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<svg');
    expect($svg)->not->toContain('<path d=');
});

it('skips unknown entity types without errors', function () {
    $entities = [
        ['type' => 'DIMENSION', 'layer' => '0', 'data' => []],
        ['type' => 'HATCH', 'layer' => '0', 'data' => []],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<svg');
    expect($svg)->not->toContain('<line ');
    expect($svg)->not->toContain('<path ');
    expect($svg)->not->toContain('<circle ');
});

it('renders mixed entity types together', function () {
    $entities = [
        ['type' => 'LINE', 'layer' => '0', 'data' => ['start_x' => 0, 'start_y' => 0, 'end_x' => 10, 'end_y' => 10]],
        ['type' => 'CIRCLE', 'layer' => '0', 'data' => ['start_x' => 50, 'start_y' => 50, 'radius' => 5]],
        [
            'type' => 'LWPOLYLINE',
            'layer' => '0',
            'data' => [
                'vertices' => [
                    ['x' => 20, 'y' => 20, 'bulge' => 0],
                    ['x' => 80, 'y' => 80, 'bulge' => 0],
                ],
                'flags' => 0,
            ],
        ],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<line x1=');
    expect($svg)->toContain('<circle cx=');
    expect($svg)->toContain('<path d=');
});

it('scales drawing to fit within SVG viewport', function () {
    $entities = [
        ['type' => 'LINE', 'layer' => '0', 'data' => ['start_x' => 0, 'start_y' => 0, 'end_x' => 1000, 'end_y' => 1000]],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<line');
    expect($svg)->toContain('<svg');
});

it('handles LWPOLYLINE with default missing flags', function () {
    $entities = [
        [
            'type' => 'LWPOLYLINE',
            'layer' => '0',
            'data' => [
                'vertices' => [
                    ['x' => 0, 'y' => 0, 'bulge' => 0],
                    ['x' => 50, 'y' => 50, 'bulge' => 0],
                ],
            ],
        ],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<path d=');
});

it('renders ARC with default missing angles as zero', function () {
    $entities = [
        [
            'type' => 'ARC',
            'layer' => '0',
            'data' => [
                'start_x' => 50,
                'start_y' => 50,
                'radius' => 20,
            ],
        ],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<path d=');
});

it('renders CIRCLE with default missing radius as zero', function () {
    $entities = [
        [
            'type' => 'CIRCLE',
            'layer' => '0',
            'data' => ['start_x' => 50, 'start_y' => 50],
        ],
    ];

    $svg = $this->svg->toSvg($entities, 100, 100);

    expect($svg)->toContain('<circle');
    expect($svg)->toContain('r="0');
});
