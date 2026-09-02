<?php

namespace App\Http\Controllers\Api;

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Http\Controllers\Controller;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChantierReserve;
use App\Models\RH\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservesSyncController extends Controller
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
     * Liste des réserves pour un chantier donné.
     */
    public function list(Request $request): JsonResponse
    {
        $employee = $request->user()->salarie;

        if (! $employee) {
            return response()->json(['data' => []]);
        }

        $chantierId = $request->input('chantier_id');
        $status = $request->input('status');

        if (! $chantierId) {
            return response()->json(['data' => []]);
        }

        $hasAccess = Chantier::forEmployee($employee)->where('id', $chantierId)->exists();

        if (! $hasAccess) {
            return response()->json(['data' => []], 403);
        }

        $query = ChantierReserve::where('chantier_id', $chantierId)
            ->with(['assignee:id,first_name,last_name']);

        if ($status) {
            $query->where('status', $status);
        }

        $reserves = $query->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($reserve) => [
                'id' => $reserve->id,
                'reference' => $reserve->reference,
                'title' => $reserve->title,
                'description' => $reserve->description,
                'severity' => $reserve->severity->value,
                'status' => $reserve->status->value,
                'assignee_name' => $reserve->assignee?->full_name,
                'due_date' => $reserve->due_date?->toDateString(),
                'created_at' => $reserve->created_at,
                'synced' => true,
            ]);

        return response()->json(['data' => $reserves]);
    }

    /**
     * Synchronise les réserves créées hors-ligne.
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
                        'CREATE_RESERVE' => $this->createReserve($payload, $employeeId),
                        default => throw new \InvalidArgumentException("Type d'opération inconnu: {$type}"),
                    };
                    $processed++;
                } catch (\Throwable $e) {
                    Log::warning("Reserves sync operation failed: {$type} - ".$e->getMessage());
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
            Log::error('Reserves sync failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Échec de la synchronisation.',
            ], 500);
        }
    }

    protected function createReserve(array $payload, int $employeeId): void
    {
        $chantierId = $payload['chantier_id'] ?? null;

        if (! $chantierId) {
            throw new \InvalidArgumentException('chantier_id requis');
        }

        $hasAccess = Chantier::forEmployee(
            Employee::findOrFail($employeeId)
        )->where('id', $chantierId)->exists();

        if (! $hasAccess) {
            throw new \InvalidArgumentException('Accès non autorisé à ce chantier');
        }

        $reserve = ChantierReserve::create([
            'chantier_id' => $chantierId,
            'title' => $payload['title'] ?? '',
            'description' => $payload['description'] ?? null,
            'severity' => $payload['severity'] ?? 'minor',
            'status' => ChantierReserveStatus::OPEN,
        ]);

        // Handle photos (base64 from offline capture)
        if (! empty($payload['photos']) && is_array($payload['photos'])) {
            foreach ($payload['photos'] as $index => $photoData) {
                if (str_starts_with($photoData, 'data:image')) {
                    $base64 = explode(',', $photoData)[1];
                    $binary = base64_decode($base64);
                    $filename = 'reserve_'.$reserve->id.'_photo_'.($index + 1).'.jpg';
                    $tempPath = sys_get_temp_dir().'/'.$filename;
                    file_put_contents($tempPath, $binary);

                    $reserve->addMedia($tempPath)
                        ->usingName($filename)
                        ->toMediaCollection('photos');

                    @unlink($tempPath);
                }
            }
        }

        ChantierLog::create([
            'chantier_id' => $chantierId,
            'user_id' => auth()->id(),
            'date' => now(),
            'content' => 'Réserve signalée depuis l\'application terrain : "'.$reserve->title.'" ('.$reserve->reference.').',
            'incident_reported' => true,
        ]);
    }
}
