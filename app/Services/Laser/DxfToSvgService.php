<?php

namespace App\Services\Laser;

class DxfToSvgService
{
    private const SVG_WIDTH = 120;

    private const SVG_HEIGHT = 120;

    private const PADDING = 5;

    public function toSvg(array $entities, float $lengthMm, float $widthMm): string
    {
        if (empty($entities) || $lengthMm <= 0 || $widthMm <= 0) {
            return '';
        }

        $bbox = $this->calculateBoundingBox($entities);

        $drawingWidth = max($bbox['max_x'] - $bbox['min_x'], 0.001);
        $drawingHeight = max($bbox['max_y'] - $bbox['min_y'], 0.001);

        $availableWidth = self::SVG_WIDTH - (self::PADDING * 2);
        $availableHeight = self::SVG_HEIGHT - (self::PADDING * 2);

        $scale = min($availableWidth / $drawingWidth, $availableHeight / $drawingHeight);

        $offsetX = self::PADDING + ($availableWidth - $drawingWidth * $scale) / 2;
        $offsetY = self::PADDING + ($availableHeight - $drawingHeight * $scale) / 2;

        $minX = $bbox['min_x'];
        $minY = $bbox['min_y'];

        $paths = [];
        foreach ($entities as $entity) {
            $paths = array_merge($paths, $this->renderEntity($entity, $scale, $offsetX, $offsetY, $minX, $minY));
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.self::SVG_WIDTH.' '.self::SVG_HEIGHT.'" style="border:1px solid #ddd; background:#fff;">';
        $svg .= '<rect width="'.self::SVG_WIDTH.'" height="'.self::SVG_HEIGHT.'" fill="none"/>';

        foreach ($paths as $path) {
            $svg .= $path;
        }

        $svg .= '</svg>';

        return $svg;
    }

    private function calculateBoundingBox(array $entities): array
    {
        $minX = PHP_FLOAT_MAX;
        $maxX = -PHP_FLOAT_MAX;
        $minY = PHP_FLOAT_MAX;
        $maxY = -PHP_FLOAT_MAX;

        foreach ($entities as $entity) {
            match ($entity['type']) {
                'LINE' => $this->lineBounds($entity, $minX, $maxX, $minY, $maxY),
                'LWPOLYLINE' => $this->polylineBounds($entity, $minX, $maxX, $minY, $maxY),
                'ARC' => $this->arcBounds($entity, $minX, $maxX, $minY, $maxY),
                'CIRCLE' => $this->circleBounds($entity, $minX, $maxX, $minY, $maxY),
                default => null,
            };
        }

        return [
            'min_x' => $minX === PHP_FLOAT_MAX ? 0 : $minX,
            'max_x' => $maxX === -PHP_FLOAT_MAX ? 0 : $maxX,
            'min_y' => $minY === PHP_FLOAT_MAX ? 0 : $minY,
            'max_y' => $maxY === -PHP_FLOAT_MAX ? 0 : $maxY,
        ];
    }

    private function lineBounds(array $entity, float &$minX, float &$maxX, float &$minY, float &$maxY): void
    {
        $d = $entity['data'];
        $this->updateBounds($d['start_x'] ?? 0, $d['start_y'] ?? 0, $minX, $maxX, $minY, $maxY);
        $this->updateBounds($d['end_x'] ?? 0, $d['end_y'] ?? 0, $minX, $maxX, $minY, $maxY);
    }

    private function polylineBounds(array $entity, float &$minX, float &$maxX, float &$minY, float &$maxY): void
    {
        foreach ($entity['data']['vertices'] ?? [] as $vertex) {
            $this->updateBounds($vertex['x'], $vertex['y'], $minX, $maxX, $minY, $maxY);
        }
    }

    private function arcBounds(array $entity, float &$minX, float &$maxX, float &$minY, float &$maxY): void
    {
        $d = $entity['data'];
        $cx = $d['start_x'] ?? 0;
        $cy = $d['start_y'] ?? 0;
        $radius = $d['radius'] ?? 0;

        $this->updateBounds($cx - $radius, $cy - $radius, $minX, $maxX, $minY, $maxY);
        $this->updateBounds($cx + $radius, $cy + $radius, $minX, $maxX, $minY, $maxY);
    }

    private function circleBounds(array $entity, float &$minX, float &$maxX, float &$minY, float &$maxY): void
    {
        $d = $entity['data'];
        $cx = $d['start_x'] ?? 0;
        $cy = $d['start_y'] ?? 0;
        $radius = $d['radius'] ?? 0;

        $this->updateBounds($cx - $radius, $cy - $radius, $minX, $maxX, $minY, $maxY);
        $this->updateBounds($cx + $radius, $cy + $radius, $minX, $maxX, $minY, $maxY);
    }

    private function updateBounds(float $x, float $y, float &$minX, float &$maxX, float &$minY, float &$maxY): void
    {
        $minX = min($minX, $x);
        $maxX = max($maxX, $x);
        $minY = min($minY, $y);
        $maxY = max($maxY, $y);
    }

    private function renderEntity(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $minY): array
    {
        return match ($entity['type']) {
            'LINE' => $this->renderLine($entity, $scale, $offsetX, $offsetY, $minX, $minY),
            'LWPOLYLINE' => $this->renderPolyline($entity, $scale, $offsetX, $offsetY, $minX, $minY),
            'ARC' => $this->renderArc($entity, $scale, $offsetX, $offsetY, $minX, $minY),
            'CIRCLE' => $this->renderCircle($entity, $scale, $offsetX, $offsetY, $minX, $minY),
            default => [],
        };
    }

    private function toSvgX(float $x, float $scale, float $offsetX, float $minX): float
    {
        return $offsetX + ($x - $minX) * $scale;
    }

    private function toSvgY(float $y, float $scale, float $offsetY, float $minY): float
    {
        return $offsetY + ($y - $minY) * $scale;
    }

    private function renderLine(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $minY): array
    {
        $d = $entity['data'];
        $x1 = $this->toSvgX($d['start_x'] ?? 0, $scale, $offsetX, $minX);
        $y1 = $this->toSvgY($d['start_y'] ?? 0, $scale, $offsetY, $minY);
        $x2 = $this->toSvgX($d['end_x'] ?? 0, $scale, $offsetX, $minX);
        $y2 = $this->toSvgY($d['end_y'] ?? 0, $scale, $offsetY, $minY);

        return ["<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"#333\" stroke-width=\"0.5\" fill=\"none\"/>"];
    }

    private function renderPolyline(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $minY): array
    {
        $vertices = $entity['data']['vertices'] ?? [];
        $count = count($vertices);
        if ($count < 2) {
            return [];
        }

        $flags = $entity['data']['flags'] ?? 0;
        $isClosed = ($flags & 1) === 1;

        $firstX = $this->toSvgX($vertices[0]['x'], $scale, $offsetX, $minX);
        $firstY = $this->toSvgY($vertices[0]['y'], $scale, $offsetY, $minY);

        $d = "M $firstX $firstY";

        $segmentCount = $isClosed ? $count : $count - 1;
        for ($i = 0; $i < $segmentCount; $i++) {
            $start = $vertices[$i];
            $end = $vertices[($i + 1) % $count];
            $bulge = $start['bulge'] ?? 0.0;

            $x2 = $this->toSvgX($end['x'], $scale, $offsetX, $minX);
            $y2 = $this->toSvgY($end['y'], $scale, $offsetY, $minY);

            if (abs($bulge) < 1e-10) {
                $d .= " L $x2 $y2";
            } else {
                $d .= ' '.$this->bulgeToSvgArc($start, $end, $bulge, $scale, $offsetX, $offsetY, $minX, $minY);
            }
        }

        if ($isClosed) {
            $d .= ' Z';
        }

        return ["<path d=\"$d\" stroke=\"#e11d48\" stroke-width=\"0.5\" fill=\"none\"/>"];
    }

    private function bulgeToSvgArc(array $start, array $end, float $bulge, float $scale, float $offsetX, float $offsetY, float $minX, float $minY): string
    {
        $dx = $end['x'] - $start['x'];
        $dy = $end['y'] - $start['y'];
        $chord = sqrt($dx * $dx + $dy * $dy);

        if ($chord < 1e-10) {
            return 'L '.$this->toSvgX($end['x'], $scale, $offsetX, $minX).' '.$this->toSvgY($end['y'], $scale, $offsetY, $minY);
        }

        $radius = $chord * (1 + $bulge ** 2) / (4 * abs($bulge));

        $tangentAngle = atan2($dy, $dx);
        $centerAngle = $tangentAngle + ($bulge > 0 ? -M_PI / 2 : M_PI / 2);
        $centerDist = $radius * cos(2 * atan(abs($bulge)));
        $midX = ($start['x'] + $end['x']) / 2;
        $midY = ($start['y'] + $end['y']) / 2;
        $cx = $midX + $centerDist * cos($centerAngle);
        $cy = $midY + $centerDist * sin($centerAngle);

        $startAngle = atan2($start['y'] - $cy, $start['x'] - $cx);
        $endAngle = atan2($end['y'] - $cy, $end['x'] - $cx);

        $sweepFlag = $bulge < 0 ? 1 : 0;

        $sweep = $endAngle - $startAngle;
        if ($bulge > 0) {
            // Counterclockwise in DXF: SVG sweep-flag = 0
            if ($sweep <= 0) {
                $sweep += 2 * M_PI;
            }
        } else {
            // Clockwise in DXF: SVG sweep-flag = 1
            if ($sweep >= 0) {
                $sweep -= 2 * M_PI;
            }
            $sweep = -$sweep;
        }

        $largeArc = $sweep > M_PI ? 1 : 0;

        $scaledRadius = $radius * $scale;
        $x2 = $this->toSvgX($end['x'], $scale, $offsetX, $minX);
        $y2 = $this->toSvgY($end['y'], $scale, $offsetY, $minY);

        return "A $scaledRadius $scaledRadius 0 $largeArc $sweepFlag $x2 $y2";
    }

    private function renderArc(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $minY): array
    {
        $d = $entity['data'];
        $cx = $this->toSvgX($d['start_x'] ?? 0, $scale, $offsetX, $minX);
        $cy = $this->toSvgY($d['start_y'] ?? 0, $scale, $offsetY, $minY);
        $radius = ($d['radius'] ?? 0) * $scale;
        $startAngle = deg2rad($d['start_angle'] ?? 0);
        $endAngle = deg2rad($d['end_angle'] ?? 0);

        $x1 = $cx + $radius * cos($startAngle);
        $y1 = $cy - $radius * sin($startAngle);
        $x2 = $cx + $radius * cos($endAngle);
        $y2 = $cy - $radius * sin($endAngle);

        $sweep = $endAngle - $startAngle;
        if ($sweep < 0) {
            $sweep += 2 * M_PI;
        }
        $largeArc = $sweep > M_PI ? 1 : 0;

        $d = "M $x1 $y1 A $radius $radius 0 $largeArc 0 $x2 $y2";

        return ["<path d=\"$d\" stroke=\"#333\" stroke-width=\"0.5\" fill=\"none\"/>"];
    }

    private function renderCircle(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $minY): array
    {
        $d = $entity['data'];
        $cx = $this->toSvgX($d['start_x'] ?? 0, $scale, $offsetX, $minX);
        $cy = $this->toSvgY($d['start_y'] ?? 0, $scale, $offsetY, $minY);
        $radius = ($d['radius'] ?? 0) * $scale;

        return ["<circle cx=\"$cx\" cy=\"$cy\" r=\"$radius\" stroke=\"#333\" stroke-width=\"0.5\" fill=\"none\"/>"];
    }
}
