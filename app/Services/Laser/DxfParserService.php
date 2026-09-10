<?php

namespace App\Services\Laser;

class DxfParserService
{
    public const DXF_UNIT = 'mm';

    private array $groupCodes = [];

    public function parse(string $content, array $allowedLayers = []): DxfImportResult
    {
        $content = $this->normalizeLineEndings($content);

        if (trim($content) === '') {
            return DxfImportResult::error('Le fichier DXF est vide.');
        }

        $this->groupCodes = $this->parseGroupCodes($content);

        if (empty($this->groupCodes)) {
            return DxfImportResult::error('Impossible de parser le fichier DXF.');
        }

        $entities = $this->extractEntities();

        if (empty($entities)) {
            return DxfImportResult::error('Aucune entité géométrique trouvée (LINE, LWPOLYLINE, ARC, CIRCLE).');
        }

        $layers = $this->extractLayers($entities);

        $cutEntities = $this->filterEntitiesByLayers($entities, $allowedLayers);

        if (empty($cutEntities)) {
            return DxfImportResult::error(
                empty($allowedLayers)
                    ? 'Aucune entité de découpe trouvée dans le fichier DXF.'
                    : 'Aucune entité de découpe trouvée dans les couches autorisées ('.implode(', ', $allowedLayers).').'
            );
        }

        $bbox = $this->calculateBoundingBox($cutEntities);
        $totalCutLength = $this->calculateCutLength($cutEntities);

        $lengthMm = max(0, $bbox['max_x'] - $bbox['min_x']);
        $widthMm = max(0, $bbox['max_y'] - $bbox['min_y']);

        return new DxfImportResult(
            lengthMm: round($lengthMm, 2),
            widthMm: round($widthMm, 2),
            totalCutLengthMm: round($totalCutLength, 2),
            entityCount: count($cutEntities),
            layers: $layers,
        );
    }

    private function normalizeLineEndings(string $content): string
    {
        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    /**
     * @return list<array{code: int, value: string}>
     */
    private function parseGroupCodes(string $content): array
    {
        $lines = explode("\n", $content);
        $pairs = [];
        $count = count($lines);

        for ($i = 0; $i < $count - 1; $i += 2) {
            $codeLine = trim($lines[$i]);
            $valueLine = trim($lines[$i + 1] ?? '');

            if ($codeLine === '' || !ctype_digit($codeLine)) {
                continue;
            }

            $pairs[] = [
                'code' => (int) $codeLine,
                'value' => $valueLine,
            ];
        }

        return $pairs;
    }

    /**
     * @return list<array{type: string, layer: string, data: array}>
     */
    private function extractEntities(): array
    {
        $entities = [];
        $count = count($this->groupCodes);
        $inEntities = false;
        $currentEntity = null;

        for ($i = 0; $i < $count; $i++) {
            $code = $this->groupCodes[$i]['code'];
            $value = $this->groupCodes[$i]['value'];

            if ($code === 0 && $value === 'SECTION' && !$inEntities) {
                $nextValue = $this->groupCodes[$i + 1]['value'] ?? '';
                if ($nextValue === 'ENTITIES') {
                    $inEntities = true;
                    $i++;
                }
                continue;
            }

            if (!$inEntities) {
                continue;
            }

            if ($code === 0) {
                if ($currentEntity !== null) {
                    $entities[] = $currentEntity;
                    $currentEntity = null;
                }

                if ($value === 'ENDSEC' || $value === 'EOF') {
                    break;
                }

                $supportedTypes = ['LINE', 'LWPOLYLINE', 'ARC', 'CIRCLE'];
                if (in_array($value, $supportedTypes, true)) {
                    $currentEntity = [
                        'type' => $value,
                        'layer' => '0',
                        'data' => [],
                    ];
                }

                continue;
            }

            if ($currentEntity === null) {
                continue;
            }

            match ($code) {
                8 => $currentEntity['layer'] = $value,
                10 => $currentEntity['data']['start_x'] = (float) $value,
                20 => $currentEntity['data']['start_y'] = (float) $value,
                11 => $currentEntity['data']['end_x'] = (float) $value,
                21 => $currentEntity['data']['end_y'] = (float) $value,
                40 => $currentEntity['data']['radius'] = (float) $value,
                42 => $this->setLastVertexBulge($currentEntity, (float) $value),
                50 => $currentEntity['data']['start_angle'] = (float) $value,
                51 => $currentEntity['data']['end_angle'] = (float) $value,
                90 => $currentEntity['data']['vertex_count'] = (int) $value,
                70 => $currentEntity['data']['flags'] = (int) $value,
                default => null,
            };

            if ($code === 10 && $currentEntity['type'] === 'LWPOLYLINE') {
                if (!isset($currentEntity['data']['vertices'])) {
                    $currentEntity['data']['vertices'] = [];
                }
                $currentEntity['data']['vertices'][] = [
                    'x' => (float) $value,
                    'y' => 0,
                    'bulge' => 0.0,
                ];
            }

            if ($code === 20 && $currentEntity['type'] === 'LWPOLYLINE') {
                $vertexCount = count($currentEntity['data']['vertices'] ?? []);
                if ($vertexCount > 0) {
                    $currentEntity['data']['vertices'][$vertexCount - 1]['y'] = (float) $value;
                }
            }
        }

        if ($currentEntity !== null) {
            $entities[] = $currentEntity;
        }

        return $entities;
    }

    private function setLastVertexBulge(array &$entity, float $bulge): void
    {
        $vertices = &$entity['data']['vertices'];
        $count = count($vertices ?? []);
        if ($count > 0) {
            $vertices[$count - 1]['bulge'] = $bulge;
        }
    }

    /**
     * @param list<array{type: string, layer: string, data: array}> $entities
     * @return array<string>
     */
    private function extractLayers(array $entities): array
    {
        $layers = [];
        foreach ($entities as $entity) {
            $layer = $entity['layer'];
            if (!in_array($layer, $layers, true)) {
                $layers[] = $layer;
            }
        }
        sort($layers);

        return $layers;
    }

    private function filterEntitiesByLayers(array $entities, array $allowedLayers): array
    {
        if (empty($allowedLayers)) {
            return $entities;
        }

        return array_values(array_filter(
            $entities,
            fn (array $entity) => in_array($entity['layer'], $allowedLayers, true),
        ));
    }

    /**
     * @param list<array{type: string, layer: string, data: array}> $entities
     * @return array{min_x: float, max_x: float, min_y: float, max_y: float}
     */
    private function calculateBoundingBox(array $entities): array
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
                'LINE' => $this->boundingBoxForLine($entity, $updateBounds),
                'LWPOLYLINE' => $this->boundingBoxForPolyline($entity, $updateBounds),
                'ARC' => $this->boundingBoxForArc($entity, $updateBounds),
                'CIRCLE' => $this->boundingBoxForCircle($entity, $updateBounds),
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

    private function boundingBoxForLine(array $entity, callable $updateBounds): void
    {
        $d = $entity['data'];
        $updateBounds($d['start_x'] ?? 0, $d['start_y'] ?? 0);
        $updateBounds($d['end_x'] ?? 0, $d['end_y'] ?? 0);
    }

    private function boundingBoxForPolyline(array $entity, callable $updateBounds): void
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
                $this->boundingBoxForBulgeArc($start, $end, $bulge, $updateBounds);
            }
        }
    }

    private function boundingBoxForBulgeArc(array $start, array $end, float $bulge, callable $updateBounds): void
    {
        $dx = $end['x'] - $start['x'];
        $dy = $end['y'] - $start['y'];
        $chord = sqrt($dx * $dx + $dy * $dy);

        if ($chord < 1e-10) {
            return;
        }

        $radius = $chord * (1 + $bulge ** 2) / (4 * abs($bulge));

        $tangentAngle = atan2($dy, $dx);
        $centerAngle = $tangentAngle + ($bulge > 0 ? -M_PI / 2 : M_PI / 2);
        $centerDist = $radius * cos(2 * atan(abs($bulge)));
        $cx = ($start['x'] + $end['x']) / 2 + $centerDist * cos($centerAngle);
        $cy = ($start['y'] + $end['y']) / 2 + $centerDist * sin($centerAngle);

        $startAngle = atan2($start['y'] - $cy, $start['x'] - $cx);
        $endAngle = atan2($end['y'] - $cy, $end['x'] - $cx);

        $updateBounds($cx + $radius * cos($startAngle), $cy + $radius * sin($startAngle));
        $updateBounds($cx + $radius * cos($endAngle), $cy + $radius * sin($endAngle));

        $checkAngles = [0, M_PI / 2, M_PI, 3 * M_PI / 2];
        $ccw = $bulge < 0;
        foreach ($checkAngles as $angle) {
            if ($this->isAngleOnArc($startAngle, $endAngle, $angle, $ccw)) {
                $updateBounds($cx + $radius * cos($angle), $cy + $radius * sin($angle));
            }
        }
    }

    private function boundingBoxForArc(array $entity, callable $updateBounds): void
    {
        $d = $entity['data'];
        $cx = $d['start_x'] ?? 0;
        $cy = $d['start_y'] ?? 0;
        $radius = $d['radius'] ?? 0;

        $startAngle = $this->normalizeAngle(deg2rad($d['start_angle'] ?? 0));
        $endAngle = $this->normalizeAngle(deg2rad($d['end_angle'] ?? 0));

        $updateBounds($cx + $radius * cos($startAngle), $cy + $radius * sin($startAngle));
        $updateBounds($cx + $radius * cos($endAngle), $cy + $radius * sin($endAngle));

        $checkAngles = [0, M_PI / 2, M_PI, 3 * M_PI / 2];
        foreach ($checkAngles as $angle) {
            if ($this->isAngleOnArc($startAngle, $endAngle, $angle)) {
                $updateBounds($cx + $radius * cos($angle), $cy + $radius * sin($angle));
            }
        }
    }

    private function normalizeAngle(float $angle): float
    {
        $angle = fmod($angle, 2 * M_PI);
        if ($angle < 0) {
            $angle += 2 * M_PI;
        }

        return $angle;
    }

    private function isAngleOnArc(float $startAngle, float $endAngle, float $angle, bool $ccw = true): bool
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

    private function boundingBoxForCircle(array $entity, callable $updateBounds): void
    {
        $d = $entity['data'];
        $cx = $d['start_x'] ?? 0;
        $cy = $d['start_y'] ?? 0;
        $radius = $d['radius'] ?? 0;

        $updateBounds($cx - $radius, $cy - $radius);
        $updateBounds($cx + $radius, $cy + $radius);
    }

    /**
     * @param list<array{type: string, layer: string, data: array}> $entities
     */
    private function calculateCutLength(array $entities): float
    {
        $total = 0.0;

        foreach ($entities as $entity) {
            $total += match ($entity['type']) {
                'LINE' => $this->lineLength($entity),
                'LWPOLYLINE' => $this->polylineLength($entity),
                'ARC' => $this->arcLength($entity),
                'CIRCLE' => $this->circleLength($entity),
                default => 0.0,
            };
        }

        return $total;
    }

    private function lineLength(array $entity): float
    {
        $d = $entity['data'];
        $dx = ($d['end_x'] ?? 0) - ($d['start_x'] ?? 0);
        $dy = ($d['end_y'] ?? 0) - ($d['start_y'] ?? 0);

        return sqrt($dx * $dx + $dy * $dy);
    }

    private function polylineLength(array $entity): float
    {
        $vertices = $entity['data']['vertices'] ?? [];
        $count = count($vertices);
        $flags = $entity['data']['flags'] ?? 0;
        $isClosed = ($flags & 1) === 1;

        if ($count < 2) {
            return 0.0;
        }

        $length = 0.0;
        $segmentCount = $isClosed ? $count : $count - 1;

        for ($i = 0; $i < $segmentCount; $i++) {
            $start = $vertices[$i];
            $end = $vertices[($i + 1) % $count];
            $bulge = $start['bulge'] ?? 0.0;

            if (abs($bulge) < 1e-10) {
                $dx = $end['x'] - $start['x'];
                $dy = $end['y'] - $start['y'];
                $length += sqrt($dx * $dx + $dy * $dy);
            } else {
                $length += $this->bulgeArcLength($start, $end, $bulge);
            }
        }

        return $length;
    }

    private function bulgeArcLength(array $start, array $end, float $bulge): float
    {
        $dx = $end['x'] - $start['x'];
        $dy = $end['y'] - $start['y'];
        $chord = sqrt($dx * $dx + $dy * $dy);

        if ($chord < 1e-10) {
            return 0.0;
        }

        $radius = $chord * (1 + $bulge ** 2) / (4 * abs($bulge));
        $theta = 4 * atan(abs($bulge));

        return $radius * $theta;
    }

    private function arcLength(array $entity): float
    {
        $d = $entity['data'];
        $radius = $d['radius'] ?? 0;
        $startAngle = deg2rad($d['start_angle'] ?? 0);
        $endAngle = deg2rad($d['end_angle'] ?? 0);

        $sweep = $endAngle - $startAngle;
        if ($sweep < 0) {
            $sweep += 2 * M_PI;
        }

        return $radius * $sweep;
    }

    private function circleLength(array $entity): float
    {
        $radius = $entity['data']['radius'] ?? 0;

        return 2 * M_PI * $radius;
    }
}
