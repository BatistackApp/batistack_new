<?php

namespace App\Services\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Services\Core\DocumentService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Service de Génération de Documents Chantiers (Impression).
 * S'appuie sur le DocumentService du Core.
 */
class ChantierDocumentService extends DocumentService
{
    public function __construct(protected ChantierAnalyticService $analyticService) {}

    /**
     * Génère la Fiche de Lancement de Chantier (Ordre de Service interne).
     */
    public function generateStartOrder(Chantier $chantier): string
    {
        $chantier->load(['client', 'manager', 'members']);

        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'title' => 'ORDRE DE SERVICE : '.$chantier->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.chantiers.start_order',
            $data,
            'os_'.$chantier->reference,
            'chantiers/orders'
        );
    }

    /**
     * Génère le Procès-Verbal (PV) de Réception de Travaux.
     * Document contractuel majeur pour la levée des réserves.
     */
    public function generateHandoverProtocol(Chantier $chantier): string
    {
        $chantier->load(['client', 'manager']);

        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'title' => 'PV DE RÉCEPTION : '.$chantier->name,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.chantiers.handover_protocol',
            $data,
            'pv_reception_'.$chantier->reference,
            'chantiers/legal'
        );
    }

    /**
     * Génère le Rapport de Rentabilité Analytique.
     */
    public function generateRentabilityReport(Chantier $chantier): string
    {
        $metrics = $this->analyticService->getPerformanceMetrics($chantier);

        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'metrics' => $metrics,
            'title' => 'BILAN ANALYTIQUE : '.$chantier->name,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.chantiers.rentability',
            $data,
            'bilan_'.$chantier->reference,
            'chantiers/reports',
            'landscape'
        );
    }

    /**
     * Génère le Journal de Chantier Hebdomadaire.
     */
    public function generateWeeklyJournal(Chantier $chantier, Carbon|CarbonInterface $startDate): string
    {
        $endDate = $startDate->copy()->endOfWeek();
        $logs = $chantier->logs()->whereBetween('date', [$startDate, $endDate])->get();

        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'logs' => $logs,
            'period' => "Semaine du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}",
            'title' => 'JOURNAL DE CHANTIER : '.$chantier->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.chantiers.journal',
            $data,
            'journal_'.$chantier->reference.'_'.$startDate->format('Y_W'),
            'chantiers/journals'
        );
    }
}
