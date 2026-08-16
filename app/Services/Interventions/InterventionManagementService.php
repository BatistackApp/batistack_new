<?php

namespace App\Services\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;

class InterventionManagementService
{
    public function scheduleIntervention(Intervention $intervention, array $employeeIds): void
    {
        // Link workers to intervention
        foreach ($employeeIds as $employeeId) {
            $intervention->workers()->firstOrCreate([
                'employee_id' => $employeeId,
            ]);
        }

        $intervention->update([
            'status' => InterventionStatus::PLANIFIEE,
        ]);

        // Notification logic will be triggered via observers or controllers
    }

    public function startIntervention(Intervention $intervention): void
    {
        $intervention->update([
            'status' => InterventionStatus::EN_COURS,
        ]);
    }

    public function completeIntervention(Intervention $intervention): bool
    {
        $this->assertReportComplete($intervention);

        $updated = $intervention->update([
            'status' => InterventionStatus::TERMINEE,
            'completed_at' => now(),
        ]);

        if ($updated) {
            // Trigger stock decrement
            app(InterventionStockService::class)->processMaterials($intervention);
        }

        return $updated;
    }

    /**
     * Vérifie que tous les champs obligatoires du modèle de rapport applicable
     * ont été renseignés avant la clôture de l'intervention.
     *
     * @throws \DomainException
     */
    public function assertReportComplete(Intervention $intervention): void
    {
        $template = $intervention->applicableReportTemplate();

        if (! $template) {
            return;
        }

        $required = collect($template->schema)
            ->filter(fn ($block) => ($block['data']['required'] ?? false) === true)
            ->mapWithKeys(fn ($block) => [$block['data']['name'] => $block['data']['label'] ?? $block['data']['name']])
            ->all();

        if (empty($required)) {
            return;
        }

        $data = $intervention->report_data ?? [];

        $missing = collect($required)
            ->filter(fn ($label, $name) => $this->isEmptyValue($data[$name] ?? null))
            ->values()
            ->all();

        if ($missing) {
            throw new \DomainException(
                'Le rapport d\'intervention est incomplet. Champs obligatoires manquants : '.implode(', ', $missing).'.'
            );
        }
    }

    protected function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [] || $value === false;
    }
}
