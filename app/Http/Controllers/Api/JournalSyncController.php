<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalSyncController extends Controller
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
     * Liste des entrées de journal pour un chantier et une date donnés.
     */
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->salarie;

        if (! $employee) {
            return response()->json(['data' => []]);
        }

        $chantierId = $request->input('chantier_id');
        $date = $request->input('date', now()->toDateString());

        if (! $chantierId) {
            return response()->json(['data' => []]);
        }

        // Vérifier que l'employé a accès à ce chantier
        $hasAccess = Chantier::forEmployee($employee)->where('id', $chantierId)->exists();

        if (! $hasAccess) {
            return response()->json(['data' => []], 403);
        }

        $logs = ChantierLog::where('chantier_id', $chantierId)
            ->whereDate('date', $date)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'chantier_id', 'user_id', 'date', 'content', 'weather_condition', 'incident_reported', 'created_at']);

        return response()->json(['data' => $logs]);
    }

    /**
     * Synchronise les entrées de journal créées hors-ligne.
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
                        'CREATE_LOG' => $this->createLog($payload, $employeeId),
                        'UPDATE_LOG' => $this->updateLog($payload, $employeeId),
                        default => throw new \InvalidArgumentException("Type d'opération inconnu: {$type}"),
                    };
                    $processed++;
                } catch (\Throwable $e) {
                    Log::warning("Journal sync operation failed: {$type} - ".$e->getMessage());
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
            Log::error('Journal sync failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Échec de la synchronisation.',
            ], 500);
        }
    }

    protected function createLog(array $payload, int $employeeId): void
    {
        $chantierId = $payload['chantier_id'] ?? null;

        if (! $chantierId) {
            throw new \InvalidArgumentException('chantier_id requis');
        }

        // Vérifier l'accès
        $hasAccess = Chantier::forEmployee(
            \App\Models\RH\Employee::findOrFail($employeeId)
        )->where('id', $chantierId)->exists();

        if (! $hasAccess) {
            throw new \InvalidArgumentException('Accès non autorisé à ce chantier');
        }

        ChantierLog::create([
            'chantier_id' => $chantierId,
            'user_id' => auth()->id(),
            'date' => $payload['date'] ?? now()->toDateString(),
            'content' => $payload['content'] ?? '',
            'weather_condition' => $payload['weather_condition'] ?? null,
            'incident_reported' => $payload['incident_reported'] ?? false,
        ]);
    }

    protected function updateLog(array $payload, int $employeeId): void
    {
        $logId = $payload['id'] ?? null;

        if (! $logId) {
            throw new \InvalidArgumentException('id requis');
        }

        $log = ChantierLog::findOrFail($logId);

        // Vérifier que l'utilisateur est l'auteur ou manage le chantier
        $hasAccess = $log->user_id === auth()->id()
            || Chantier::forEmployee(
                \App\Models\RH\Employee::findOrFail($employeeId)
            )->where('id', $log->chantier_id)->exists();

        if (! $hasAccess) {
            throw new \InvalidArgumentException('Accès non autorisé');
        }

        $log->update([
            'content' => $payload['content'] ?? $log->content,
            'weather_condition' => $payload['weather_condition'] ?? $log->weather_condition,
            'incident_reported' => $payload['incident_reported'] ?? $log->incident_reported,
        ]);
    }
}
