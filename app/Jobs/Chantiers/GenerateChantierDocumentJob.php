<?php

namespace App\Jobs\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierDocumentService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateChantierDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Chantier $chantier,
        protected string $documentType // 'rentability', 'journal', 'os'
    ) {}

    public function handle(ChantierDocumentService $service): void
    {
        try {
            match ($this->documentType) {
                'rentability' => $service->generateRentabilityReport($this->chantier),
                'journal' => $service->generateWeeklyJournal($this->chantier, now()->startOfWeek()),
                'os' => $service->generateStartOrder($this->chantier),
                'ppsps' => $service->generatePpsps($this->chantier),
                default => throw new Exception('Type de document inconnu.')
            };
        } catch (Exception $e) {
            Log::error("Erreur génération doc {$this->documentType} pour chantier #{$this->chantier->id} : ".$e->getMessage());
        }
    }
}
