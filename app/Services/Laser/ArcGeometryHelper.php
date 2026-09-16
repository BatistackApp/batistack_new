<?php

namespace App\Services\Laser;

class ArcGeometryHelper
{
    public function chordLength(array $start, array $end): float
    {
        $dx = $end['x'] - $start['x'];
        $dy = $end['y'] - $start['y'];

        return sqrt($dx * $dx + $dy * $dy);
    }

    public function radius(float $chord, float $bulge): float
    {
        return $chord * (1 + $bulge ** 2) / (4 * abs($bulge));
    }

    public function includedAngle(float $bulge): float
    {
        return 4 * atan(abs($bulge));
    }

    public function arcLength(float $chord, float $bulge): float
    {
        if ($chord < 1e-10) {
            return 0.0;
        }

        $radius = $this->radius($chord, $bulge);
        $theta = $this->includedAngle($bulge);

        return $radius * $theta;
    }

    public function center(array $start, array $end, float $bulge): array
    {
        $chord = $this->chordLength($start, $end);

        if ($chord < 1e-10) {
            return ['x' => $start['x'], 'y' => $start['y']];
        }

        $radius = $this->radius($chord, $bulge);
        $tangentAngle = atan2($end['y'] - $start['y'], $end['x'] - $start['x']);
        $centerAngle = $tangentAngle + ($bulge > 0 ? -M_PI / 2 : M_PI / 2);
        $centerDist = $radius * cos(2 * atan(abs($bulge)));

        return [
            'x' => ($start['x'] + $end['x']) / 2 + $centerDist * cos($centerAngle),
            'y' => ($start['y'] + $end['y']) / 2 + $centerDist * sin($centerAngle),
        ];
    }

    public function anglesFromCenter(array $point, array $center): array
    {
        return [
            atan2($point['y'] - $center['y'], $point['x'] - $center['x']),
            atan2($point['y'] - $center['y'], $point['x'] - $center['x']),
        ];
    }

    public function startAngle(array $start, array $center): float
    {
        return atan2($start['y'] - $center['y'], $start['x'] - $center['x']);
    }

    public function endAngle(array $end, array $center): float
    {
        return atan2($end['y'] - $center['y'], $end['x'] - $center['x']);
    }

    public function normalizeAngle(float $angle): float
    {
        $angle = fmod($angle, 2 * M_PI);
        if ($angle < 0) {
            $angle += 2 * M_PI;
        }

        return $angle;
    }

    /**
     * Determine if a cardinal angle lies on the arc path between startAngle and endAngle.
     *
     * For DXF bulge arcs:
     * - bulge > 0: arc curves LEFT of chord direction → traversal is CW in terms of angles from center
     * - bulge < 0: arc curves RIGHT of chord direction → traversal is CCW in terms of angles from center
     *
     * So $ccw parameter = ($bulge < 0), NOT ($bulge > 0).
     */
    public function isAngleOnArc(float $startAngle, float $endAngle, float $angle, bool $ccw = true): bool
    {
        $startAngle = $this->normalizeAngle($startAngle);
        $endAngle = $this->normalizeAngle($endAngle);
        $angle = $this->normalizeAngle($angle);

        if ($ccw) {
            if ($startAngle <= $endAngle) {
                return $angle >= $startAngle && $angle <= $endAngle;
            }

            return $angle >= $startAngle || $angle <= $endAngle;
        }

        if ($startAngle >= $endAngle) {
            return $angle <= $startAngle && $angle >= $endAngle;
        }

        return $angle <= $startAngle || $angle >= $endAngle;
    }

    /**
     * Calculate SVG arc flags from DXF bulge.
     *
     * @return array{largeArc: int, sweepFlag: int}
     */
    public function svgArcFlags(float $bulge): array
    {
        $includedAngle = $this->includedAngle($bulge);

        return [
            'largeArc' => $includedAngle > M_PI ? 1 : 0,
            'sweepFlag' => $bulge < 0 ? 1 : 0,
        ];
    }

    /**
     * Convert a DXF bulge arc to an SVG arc path segment.
     */
    public function bulgeToSvgArc(array $start, array $end, float $bulge, float $scale, float $offsetX, float $offsetY, float $minX, float $maxY): string
    {
        $chord = $this->chordLength($start, $end);

        if ($chord < 1e-10) {
            $x = $offsetX + ($end['x'] - $minX) * $scale;
            $y = $offsetY + ($maxY - $end['y']) * $scale;

            return "L $x $y";
        }

        $radius = $this->radius($chord, $bulge);
        $center = $this->center($start, $end, $bulge);
        $sAngle = $this->startAngle($start, $center);
        $eAngle = $this->endAngle($end, $center);

        $flags = $this->svgArcFlags($bulge);

        $scaledRadius = $radius * $scale;
        $x2 = $offsetX + ($end['x'] - $minX) * $scale;
        $y2 = $offsetY + ($maxY - $end['y']) * $scale;

        return "A $scaledRadius $scaledRadius 0 {$flags['largeArc']} {$flags['sweepFlag']} $x2 $y2";
    }
}
