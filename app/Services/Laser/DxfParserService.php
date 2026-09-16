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
            entities: $cutEntities,
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

            if ($codeLine === '' || ! ctype_digit($codeLine)) {
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
        $currentVertex = null;

        for ($i = 0; $i < $count; $i++) {
            $code = $this->groupCodes[$i]['code'];
            $value = $this->groupCodes[$i]['value'];

            if ($code === 0 && $value === 'SECTION' && ! $inEntities) {
                $nextValue = $this->groupCodes[$i + 1]['value'] ?? '';
                if ($nextValue === 'ENTITIES') {
                    $inEntities = true;
                    $i++;
                }

                continue;
            }

            if (! $inEntities) {
                continue;
            }

            if ($code === 0) {
                if ($value === 'VERTEX' && $currentEntity !== null && ($currentEntity['data']['legacy_polyline'] ?? false)) {
                    // Finalize previous vertex if any
                    if ($currentVertex !== null) {
                        $currentEntity['data']['vertices'][] = $currentVertex;
                    }
                    $currentVertex = [
                        'x' => 0.0,
                        'y' => 0.0,
                        'bulge' => 0.0,
                    ];

                    continue;
                }

                if ($value === 'SEQEND' && $currentEntity !== null && ($currentEntity['data']['legacy_polyline'] ?? false)) {
                    // Finalize last vertex if any
                    if ($currentVertex !== null) {
                        $currentEntity['data']['vertices'][] = $currentVertex;
                        $currentVertex = null;
                    }

                    continue;
                }

                if ($currentEntity !== null) {
                    // Finalize any pending vertex before storing the entity
                    if ($currentVertex !== null) {
                        $currentEntity['data']['vertices'][] = $currentVertex;
                        $currentVertex = null;
                    }
                    $entities[] = $currentEntity;
                    $currentEntity = null;
                }

                if ($value === 'ENDSEC' || $value === 'EOF') {
                    break;
                }

                $supportedTypes = ['LINE', 'LWPOLYLINE', 'POLYLINE', 'ARC', 'CIRCLE'];
                if (in_array($value, $supportedTypes, true)) {
                    $currentEntity = [
                        'type' => $value === 'POLYLINE' ? 'LWPOLYLINE' : $value,
                        'layer' => '0',
                        'data' => $value === 'POLYLINE'
                            ? ['vertices' => [], 'legacy_polyline' => true]
                            : [],
                    ];
                }

                continue;
            }

            if ($currentEntity === null) {
                continue;
            }

            $isLegacy = $currentEntity['data']['legacy_polyline'] ?? false;
            $inVertex = $isLegacy && $currentVertex !== null;

            if ($inVertex) {
                // Group codes inside a VERTEX go into $currentVertex, not the parent entity
                match ($code) {
                    8 => $currentVertex['layer'] = $value,
                    10 => $currentVertex['x'] = (float) $value,
                    20 => $currentVertex['y'] = (float) $value,
                    42 => $currentVertex['bulge'] = (float) $value,
                    70 => $currentVertex['flags'] = (int) $value,
                    default => null,
                };

                continue;
            }

            // Standard entity group codes (non-VERTEX context)
            match ($code) {
                8 => $currentEntity['layer'] = $value,
                10 => $this->handleCode10($currentEntity, $value, $isLegacy),
                20 => $this->handleCode20($currentEntity, $value, $isLegacy),
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
        }

        if ($currentEntity !== null) {
            // Finalize any pending vertex
            if ($currentVertex !== null) {
                $currentEntity['data']['vertices'][] = $currentVertex;
            }
            $entities[] = $currentEntity;
        }

        return $entities;
    }

    private function handleCode10(array &$entity, string $value, bool $isLegacy): void
    {
        if ($entity['type'] !== 'LWPOLYLINE') {
            $entity['data']['start_x'] = (float) $value;

            return;
        }

        if ($isLegacy) {
            $vertexCount = count($entity['data']['vertices'] ?? []);
            if ($vertexCount > 0) {
                $entity['data']['vertices'][$vertexCount - 1]['x'] = (float) $value;
            }
        } else {
            $entity['data']['vertices'][] = [
                'x' => (float) $value,
                'y' => 0,
                'bulge' => 0.0,
            ];
        }
    }

    private function handleCode20(array &$entity, string $value, bool $isLegacy): void
    {
        if ($entity['type'] !== 'LWPOLYLINE') {
            $entity['data']['start_y'] = (float) $value;

            return;
        }

        $vertexCount = count($entity['data']['vertices'] ?? []);
        if ($vertexCount > 0) {
            $entity['data']['vertices'][$vertexCount - 1]['y'] = (float) $value;
        }
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
     * @param  list<array{type: string, layer: string, data: array}>  $entities
     * @return array<string>
     */
    private function extractLayers(array $entities): array
    {
        $layers = [];
        foreach ($entities as $entity) {
            $layer = $entity['layer'];
            if (! in_array($layer, $layers, true)) {
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
     * @param  list<array{type: string, layer: string, data: array}>  $entities
     * @return array{min_x: float, max_x: float, min_y: float, max_y: float}
     */
    private function calculateBoundingBox(array $entities): array
    {
        return app(BoundingBoxCalculator::class)->calculate($entities);
    }

    /**
     * @param  list<array{type: string, layer: string, data: array}>  $entities
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
        $chord = app(ArcGeometryHelper::class)->chordLength($start, $end);

        return app(ArcGeometryHelper::class)->arcLength($chord, $bulge);
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
