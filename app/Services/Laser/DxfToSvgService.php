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

        $bbox = app(BoundingBoxCalculator::class)->calculate($entities);

        $drawingWidth = max($bbox['max_x'] - $bbox['min_x'], 0.001);
        $drawingHeight = max($bbox['max_y'] - $bbox['min_y'], 0.001);

        $availableWidth = self::SVG_WIDTH - (self::PADDING * 2);
        $availableHeight = self::SVG_HEIGHT - (self::PADDING * 2);

        $scale = min($availableWidth / $drawingWidth, $availableHeight / $drawingHeight);

        $offsetX = self::PADDING + ($availableWidth - $drawingWidth * $scale) / 2;
        $offsetY = self::PADDING + ($availableHeight - $drawingHeight * $scale) / 2;

        $minX = $bbox['min_x'];
        $maxY = $bbox['max_y'];

        $paths = [];
        foreach ($entities as $entity) {
            $paths = array_merge($paths, $this->renderEntity($entity, $scale, $offsetX, $offsetY, $minX, $maxY));
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.self::SVG_WIDTH.' '.self::SVG_HEIGHT.'" style="border:1px solid #ddd; background:#fff;">';
        $svg .= '<rect width="'.self::SVG_WIDTH.'" height="'.self::SVG_HEIGHT.'" fill="none"/>';

        foreach ($paths as $path) {
            $svg .= $path;
        }

        $svg .= '</svg>';

        return $svg;
    }

    private function toSvgX(float $x, float $scale, float $offsetX, float $minX): float
    {
        return $offsetX + ($x - $minX) * $scale;
    }

    private function toSvgY(float $y, float $scale, float $offsetY, float $maxY): float
    {
        return $offsetY + ($maxY - $y) * $scale;
    }

    private function renderEntity(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $maxY): array
    {
        return match ($entity['type']) {
            'LINE' => $this->renderLine($entity, $scale, $offsetX, $offsetY, $minX, $maxY),
            'LWPOLYLINE' => $this->renderPolyline($entity, $scale, $offsetX, $offsetY, $minX, $maxY),
            'ARC' => $this->renderArc($entity, $scale, $offsetX, $offsetY, $minX, $maxY),
            'CIRCLE' => $this->renderCircle($entity, $scale, $offsetX, $offsetY, $minX, $maxY),
            default => [],
        };
    }

    private function renderLine(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $maxY): array
    {
        $d = $entity['data'];
        $x1 = $this->toSvgX($d['start_x'] ?? 0, $scale, $offsetX, $minX);
        $y1 = $this->toSvgY($d['start_y'] ?? 0, $scale, $offsetY, $maxY);
        $x2 = $this->toSvgX($d['end_x'] ?? 0, $scale, $offsetX, $minX);
        $y2 = $this->toSvgY($d['end_y'] ?? 0, $scale, $offsetY, $maxY);

        return ["<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"#333\" stroke-width=\"0.5\" fill=\"none\"/>"];
    }

    private function renderPolyline(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $maxY): array
    {
        $vertices = $entity['data']['vertices'] ?? [];
        $count = count($vertices);
        if ($count < 2) {
            return [];
        }

        $flags = $entity['data']['flags'] ?? 0;
        $isClosed = ($flags & 1) === 1;

        $firstX = $this->toSvgX($vertices[0]['x'], $scale, $offsetX, $minX);
        $firstY = $this->toSvgY($vertices[0]['y'], $scale, $offsetY, $maxY);

        $d = "M $firstX $firstY";

        $segmentCount = $isClosed ? $count : $count - 1;
        for ($i = 0; $i < $segmentCount; $i++) {
            $start = $vertices[$i];
            $end = $vertices[($i + 1) % $count];
            $bulge = $start['bulge'] ?? 0.0;

            $x2 = $this->toSvgX($end['x'], $scale, $offsetX, $minX);
            $y2 = $this->toSvgY($end['y'], $scale, $offsetY, $maxY);

            if (abs($bulge) < 1e-10) {
                $d .= " L $x2 $y2";
            } else {
                $d .= ' '.$this->bulgeToSvgArc($start, $end, $bulge, $scale, $offsetX, $offsetY, $minX, $maxY);
            }
        }

        if ($isClosed) {
            $d .= ' Z';
        }

        return ["<path d=\"$d\" stroke=\"#e11d48\" stroke-width=\"0.5\" fill=\"none\"/>"];
    }

    private function bulgeToSvgArc(array $start, array $end, float $bulge, float $scale, float $offsetX, float $offsetY, float $minX, float $maxY): string
    {
        return app(ArcGeometryHelper::class)->bulgeToSvgArc($start, $end, $bulge, $scale, $offsetX, $offsetY, $minX, $maxY);
    }

    private function renderArc(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $maxY): array
    {
        $d = $entity['data'];
        $cx = $d['start_x'] ?? 0;
        $cy = $d['start_y'] ?? 0;
        $radius = $d['radius'] ?? 0;
        $startAngle = deg2rad($d['start_angle'] ?? 0);
        $endAngle = deg2rad($d['end_angle'] ?? 0);

        $x1 = $this->toSvgX($cx + $radius * cos($startAngle), $scale, $offsetX, $minX);
        $y1 = $this->toSvgY($cy + $radius * sin($startAngle), $scale, $offsetY, $maxY);
        $x2 = $this->toSvgX($cx + $radius * cos($endAngle), $scale, $offsetX, $minX);
        $y2 = $this->toSvgY($cy + $radius * sin($endAngle), $scale, $offsetY, $maxY);

        $sweep = $endAngle - $startAngle;
        if ($sweep < 0) {
            $sweep += 2 * M_PI;
        }
        $largeArc = $sweep > M_PI ? 1 : 0;

        $svgCx = $this->toSvgX($cx, $scale, $offsetX, $minX);
        $svgCy = $this->toSvgY($cy, $scale, $offsetY, $maxY);
        $scaledRadius = $radius * $scale;

        $d = "M $x1 $y1 A $scaledRadius $scaledRadius 0 $largeArc 0 $x2 $y2";

        return ["<path d=\"$d\" stroke=\"#333\" stroke-width=\"0.5\" fill=\"none\"/>"];
    }

    private function renderCircle(array $entity, float $scale, float $offsetX, float $offsetY, float $minX, float $maxY): array
    {
        $d = $entity['data'];
        $cx = $this->toSvgX($d['start_x'] ?? 0, $scale, $offsetX, $minX);
        $cy = $this->toSvgY($d['start_y'] ?? 0, $scale, $offsetY, $maxY);
        $radius = ($d['radius'] ?? 0) * $scale;

        return ["<circle cx=\"$cx\" cy=\"$cy\" r=\"$radius\" stroke=\"#333\" stroke-width=\"0.5\" fill=\"none\"/>"];
    }
}
