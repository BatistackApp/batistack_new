<?php

namespace App\Services\Laser;

class DxfImportResult
{
    public function __construct(
        public readonly float $lengthMm,
        public readonly float $widthMm,
        public readonly float $totalCutLengthMm,
        public readonly int $entityCount,
        public readonly array $layers,
        public readonly ?string $error = null,
    ) {}

    public function isValid(): bool
    {
        return $this->error === null;
    }

    public static function error(string $message): self
    {
        return new self(
            lengthMm: 0,
            widthMm: 0,
            totalCutLengthMm: 0,
            entityCount: 0,
            layers: [],
            error: $message,
        );
    }
}
