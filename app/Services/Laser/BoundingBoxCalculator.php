<?php

namespace App\Services\Laser;

class BoundingBoxCalculator
{
    private ArcGeometryHelper $arcHelper;

    public function __construct(ArcGeometryHelper $arcHelper)
    {
        $this->arcHelper = $arcHelper;
    }

    public function calculate(array $entities): array
    {
        $minX = PHP_FLOAT_MAX;
        $maxX = -PHP_FLOAT_MAX;
        $minY = PHP_FLOAT_MAX;
        $maxY = -PHP_FLOAT_MAX;

        $updateBounds = function (float $x, float $y) use (&$minX, &$maxX, &$minY, &$maxY): void {
            $minX = min($minX, $x);
            $maxX = max($maxX, $x);
            $minY = min($minY, $y);
            $maxY = max($maxY, $y);
        };

        foreach ($entities as $entity) {
            match ($entity['type']) {
                'LINE' => $this->forLine($entity, $updateBounds),
                'LWPOLYLINE' => $this->forPolyline($entity, $updateBounds),
                'ARC' => $this->forArc($entity, $updateBounds),
                'CIRCLE' => $this->forCircle($entity, $updateBounds),
                default => null,
            };
        }

        if ($minX === PHP_FLOAT_MAX) {
            return ['min_x' => 0, 'max_x' => 0, 'min_y' => 0, 'max_y' => 0];
        }

        return [
            'min_x' => $minX,
            'max_x' => $maxX,
            'min_y' => $minY,
            'max_y' => $maxY,
        ];
    }

    private function forLine(array $entity, callable $updateBounds): void
    {
        $d = $entity['data'];
        $updateBounds($d['start_x'] ?? 0, $d['start_y'] ?? 0);
        $updateBounds($d['end_x'] ?? 0, $d['end_y'] ?? 0);
    }

    private function forPolyline(array $entity, callable $updateBounds): void
    {
        $vertices = $entity['data']['vertices'] ?? [];
        $count = count($vertices);
        $flags = $entity['data']['flags'] ?? 0;
        $isClosed = ($flags & 1) === 1;

        foreach ($vertices as $vertex) {
            $updateBounds($vertex['x'], $vertex['y']);
        }

        $segmentCount = $isClosed ? $count : $count - 1;
        for ($i = 0; $i < $segmentCount; $i++) {
            $start = $vertices[$i];
            $end = $vertices[($i + 1) % $count];
            $bulge = $start['bulge'] ?? 0.0;

            if (abs($bulge) >= 1e-10) {
                $this->forBulgeArc($start, $end, $bulge, $updateBounds);
            }
        }
    }

    private function forBulgeArc(array $start, array $end, float $bulge, callable $updateBounds): void
    {
        $chord = $this->arcHelper->chordLength($start, $end);

        if ($chord < 1e-10) {
            return;
        }

        $radius = $this->arcHelper->radius($chord, $bulge);
        $center = $this->arcHelper->center($start, $end, $bulge);
        $cx = $center['x'];
        $cy = $center['y'];

        $startAngle = $this->arcHelper->startAngle($start, $center);
        $endAngle = $this->arcHelper->endAngle($end, $center);

        $updateBounds($cx + $radius * cos($startAngle), $cy + $radius * sin($startAngle));
        $updateBounds($cx + $radius * cos($endAngle), $cy + $radius * sin($endAngle));

        // Positive DXF bulges traverse counter-clockwise around the centre.
        $ccw = $bulge > 0;
        $checkAngles = [0, M_PI / 2, M_PI, 3 * M_PI / 2];
        foreach ($checkAngles as $angle) {
            if ($this->arcHelper->isAngleOnArc($startAngle, $endAngle, $angle, $ccw)) {
                $updateBounds($cx + $radius * cos($angle), $cy + $radius * sin($angle));
            }
        }
    }

    private function forArc(array $entity, callable $updateBounds): void
    {
        $d = $entity['data'];
        $cx = $d['start_x'] ?? 0;
        $cy = $d['start_y'] ?? 0;
        $radius = $d['radius'] ?? 0;

        $startAngle = $this->arcHelper->normalizeAngle(deg2rad($d['start_angle'] ?? 0));
        $endAngle = $this->arcHelper->normalizeAngle(deg2rad($d['end_angle'] ?? 0));

        $updateBounds($cx + $radius * cos($startAngle), $cy + $radius * sin($startAngle));
        $updateBounds($cx + $radius * cos($endAngle), $cy + $radius * sin($endAngle));

        $checkAngles = [0, M_PI / 2, M_PI, 3 * M_PI / 2];
        foreach ($checkAngles as $angle) {
            if ($this->arcHelper->isAngleOnArc($startAngle, $endAngle, $angle)) {
                $updateBounds($cx + $radius * cos($angle), $cy + $radius * sin($angle));
            }
        }
    }

    private function forCircle(array $entity, callable $updateBounds): void
    {
        $d = $entity['data'];
        $cx = $d['start_x'] ?? 0;
        $cy = $d['start_y'] ?? 0;
        $radius = $d['radius'] ?? 0;

        $updateBounds($cx - $radius, $cy - $radius);
        $updateBounds($cx + $radius, $cy + $radius);
    }

    public function normalizeAngle(float $angle): float
    {
        return $this->arcHelper->normalizeAngle($angle);
    }

    public function isAngleOnArc(float $startAngle, float $endAngle, float $angle, bool $ccw = true): bool
    {
        return $this->arcHelper->isAngleOnArc($startAngle, $endAngle, $angle, $ccw);
    }
}
