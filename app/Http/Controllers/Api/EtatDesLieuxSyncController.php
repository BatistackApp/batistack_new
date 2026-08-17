<?php

namespace App\Http\Controllers\Api;

use App\Enums\Locations\RentalStatus;
use App\Http\Controllers\Controller;
use App\Models\Locations\RentalConditionReport;
use App\Models\Locations\RentalContract;
use App\Services\Locations\RentalConditionReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EtatDesLieuxSyncController extends Controller
{
    /**
     * Liste des contrats de location (entrants) des chantiers gérés par le chef de chantier.
     */
    public function index(Request $request)
    {
        $employeeId = $request->user()->salarie?->id;

        if (! $employeeId) {
            return response()->json(['data' => []], 200);
        }

        $contracts = RentalContract::query()
            ->with(['chantier:id,name,reference', 'lines'])
            ->whereHas('chantier', function ($q) use ($employeeId) {
                $q->where('manager_id', $employeeId)
                    ->orWhereHas('members', fn ($m) => $m->where('employees.id', $employeeId));
            })
            ->where('status', '!=', RentalStatus::TERMINATED->value)
            ->get();

        return response()->json(['data' => $contracts], 200);
    }

    /**
     * Synchronise les états des lieux créés hors-ligne (reports + photos).
     */
    public function sync(Request $request, RentalConditionReportService $service)
    {
        $user = $request->user();

        if (! $user->salarie) {
            return response()->json(['error' => 'Utilisateur sans fiche employé.'], 403);
        }

        $operations = $request->input('operations', []);
        $processed = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($operations as $operation) {
                $type = $operation['type'] ?? null;
                $payload = $operation['payload'] ?? [];

                if ($type === 'CREATE_REPORT') {
                    $report = $service->createFromSync($user, $payload);
                    if ($report) {
                        $processed++;
                    } else {
                        $failed++;
                    }
                } elseif ($type === 'UPLOAD_PHOTO') {
                    $report = RentalConditionReport::where('client_key', $payload['report_key'] ?? '')->first();
                    if ($report
                        && ! empty($payload['image'])
                        && $service->userManagesContract($user, $report->rental_contract_id)
                        && $service->attachPhoto($report, $payload['image'], $payload['filename'] ?? null)
                    ) {
                        $processed++;
                    } else {
                        $failed++;
                    }
                } else {
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
            Log::error('Etat des lieux sync failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Échec de la synchronisation.',
            ], 500);
        }
    }
}
