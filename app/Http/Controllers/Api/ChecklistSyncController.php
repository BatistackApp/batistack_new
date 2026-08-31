<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChecklistSubmission;
use App\Models\Chantiers\ChecklistTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChecklistSyncController extends Controller
{
    /**
     * Liste des chantiers accessibles par le chef de chantier.
     */
    public function chantiers(Request $request): JsonResponse
    {
        $employee = $request->user()->salarie;

        if (! $employee) {
            return response()->json(['data' => []]);
        }

        $chantiers = Chantier::forEmployee($employee)
            ->select('id', 'name', 'reference', 'status')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $chantiers]);
    }

    /**
     * Liste des modèles de checklist actifs.
     */
    public function templates(Request $request): JsonResponse
    {
        $templates = ChecklistTemplate::where('is_active', true)
            ->select('id', 'name', 'description', 'schema')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $templates]);
    }

    /**
     * Liste des soumissions de checklist pour un chantier donné.
     */
    public function submissions(Request $request): JsonResponse
    {
        $employee = $request->user()->salarie;

        if (! $employee) {
            return response()->json(['data' => []]);
        }

        $chantierId = $request->input('chantier_id');

        if (! $chantierId) {
            return response()->json(['data' => []]);
        }

        $hasAccess = Chantier::forEmployee($employee)->where('id', $chantierId)->exists();

        if (! $hasAccess) {
            return response()->json(['data' => []], 403);
        }

        $submissions = ChecklistSubmission::where('chantier_task_id', function ($q) use ($chantierId) {
            $q->whereIn('chantier_task_id', function ($q2) use ($chantierId) {
                $q2->select('id')
                    ->from('chantier_tasks')
                    ->whereIn('chantier_phase_id', function ($q3) use ($chantierId) {
                        $q3->select('id')
                            ->from('chantier_phases')
                            ->where('chantier_id', $chantierId);
                    });
            });
        })
            ->with('template:id,name')
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'chantier_task_id' => $sub->chantier_task_id,
                'checklist_template_id' => $sub->checklist_template_id,
                'template_name' => $sub->template?->name ?? 'Checklist',
                'completed_items' => collect($sub->data)->filter()->count(),
                'total_items' => count($sub->data),
                'created_at' => $sub->created_at,
            ]);

        return response()->json(['data' => $submissions]);
    }

    /**
     * Synchronise les checklists créées hors-ligne.
     */
    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->salarie) {
            return response()->json(['error' => 'Utilisateur sans fiche employé.'], 403);
        }

        $employeeId = $user->salarie->id;
        $operations = $request->input('operations', []);
        $processed = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($operations as $operation) {
                $type = $operation['type'] ?? null;
                $payload = $operation['payload'] ?? [];

                try {
                    match ($type) {
                        'CREATE_SUBMISSION' => $this->createSubmission($payload, $employeeId),
                        default => throw new \InvalidArgumentException("Type d'opération inconnu: {$type}"),
                    };
                    $processed++;
                } catch (\Throwable $e) {
                    Log::warning("Checklist sync operation failed: {$type} - ".$e->getMessage());
                    $failed++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'processed' => $processed,
                'failed' => $failed,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Checklist sync failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Échec de la synchronisation.',
            ], 500);
        }
    }

    protected function createSubmission(array $payload, int $employeeId): void
    {
        $chantierId = $payload['chantier_id'] ?? null;
        $templateId = $payload['checklist_template_id'] ?? null;

        if (! $chantierId || ! $templateId) {
            throw new \InvalidArgumentException('chantier_id et checklist_template_id requis');
        }

        $hasAccess = Chantier::forEmployee(
            \App\Models\RH\Employee::findOrFail($employeeId)
        )->where('id', $chantierId)->exists();

        if (! $hasAccess) {
            throw new \InvalidArgumentException('Accès non autorisé à ce chantier');
        }

        $template = ChecklistTemplate::findOrFail($templateId);

        // Find the first task of the first phase of this chantier for linking
        $firstTask = \App\Models\Chantiers\ChantierTask::whereHas('phase', fn ($q) => $q->where('chantier_id', $chantierId))
            ->first();

        ChecklistSubmission::create([
            'checklist_template_id' => $templateId,
            'chantier_task_id' => $firstTask?->id,
            'submitted_by' => auth()->id(),
            'data' => $payload['data'] ?? [],
        ]);

        ChantierLog::create([
            'chantier_id' => $chantierId,
            'user_id' => auth()->id(),
            'date' => now(),
            'content' => 'Checklist "'.$template->name.'" complétée via l\'application terrain.',
            'incident_reported' => false,
        ]);
    }
}
