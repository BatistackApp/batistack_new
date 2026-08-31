<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierEquipmentTracking;
use App\Models\RH\Equipement;
use App\Models\Immobilisation\FixedAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChantierEquipmentSyncController extends Controller
{
    /**
     * Liste des chantiers accessibles par l'utilisateur.
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
     * Matériel actuellement présent sur un chantier.
     */
    public function presence(Request $request): JsonResponse
    {
        $chantierId = $request->input('chantier_id');

        $query = ChantierEquipmentTracking::query()
            ->with('trackable', 'chantier')
            ->whereDate('check_in_at', today());

        if ($chantierId) {
            $query->where('chantier_id', $chantierId);
        }

        $trackings = $query->latest('check_in_at')->get()
            ->map(fn ($tracking) => [
                'id' => $tracking->id,
                'label' => $tracking->getTrackableLabel(),
                'type_label' => $tracking->getTrackableTypeLabel(),
                'chantier_name' => $tracking->chantier?->name ?? '',
                'check_in_at' => $tracking->check_in_at->toIso8601String(),
                'check_out_at' => $tracking->check_out_at?->toIso8601String(),
                'check_in_time' => $tracking->check_in_at->format('H:i'),
                'is_out' => $tracking->check_out_at !== null,
                'duration_days' => $tracking->getDurationInDays(),
                'daily_rate' => $tracking->getDailyRate(),
                'cost' => $tracking->getImmobilizationCost(),
            ]);

        return response()->json(['data' => $trackings]);
    }

    /**
     * Enregistrer un scan (check_in ou check_out).
     */
    public function scan(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'qr_token' => 'required|string',
            'chantier_id' => 'required|integer|exists:chantiers,id',
            'action' => 'required|in:check_in,check_out',
            'notes' => 'nullable|string',
        ]);

        $qrToken = $validated['qr_token'];
        $chantierId = $validated['chantier_id'];
        $action = $validated['action'];

        // Resolve equipment by QR token
        $trackable = FixedAsset::where('qr_token', $qrToken)->first();

        if (! $trackable) {
            $trackable = Equipement::where('qr_token', $qrToken)
                ->orWhere('serial_number', $qrToken)
                ->orWhere('barcode', $qrToken)
                ->first();
        }

        if (! $trackable) {
            return response()->json([
                'success' => false,
                'error' => 'Matériel introuvable pour ce code.',
            ], 404);
        }

        $trackableType = get_class($trackable);

        try {
            DB::beginTransaction();

            if ($action === 'check_in') {
                // Check for existing open tracking
                $existing = ChantierEquipmentTracking::where('trackable_type', $trackableType)
                    ->where('trackable_id', $trackable->id)
                    ->whereNull('check_out_at')
                    ->first();

                if ($existing) {
                    // Already on this chantier — no duplicate
                    if ($existing->chantier_id == $chantierId) {
                        DB::rollBack();

                        return response()->json([
                            'success' => true,
                            'message' => 'Déjà présent sur ce chantier',
                            'tracking_id' => $existing->id,
                            'label' => $existing->getTrackableLabel(),
                        ]);
                    }

                    // Auto check-out from previous chantier
                    $existing->update(['check_out_at' => now()]);
                }

                $tracking = ChantierEquipmentTracking::create([
                    'chantier_id' => $chantierId,
                    'trackable_type' => $trackableType,
                    'trackable_id' => $trackable->id,
                    'scanned_by' => $user->id,
                    'check_in_at' => now(),
                    'qr_token' => $qrToken,
                    'notes' => $validated['notes'] ?? null,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'action' => 'check_in',
                    'tracking_id' => $tracking->id,
                    'label' => $tracking->getTrackableLabel(),
                ]);

            } else {
                // Check out
                $tracking = ChantierEquipmentTracking::where('trackable_type', $trackableType)
                    ->where('trackable_id', $trackable->id)
                    ->whereNull('check_out_at')
                    ->first();

                if (! $tracking) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'error' => 'Aucune session ouverte trouvée pour ce matériel.',
                    ], 404);
                }

                $tracking->update([
                    'check_out_at' => now(),
                    'notes' => $validated['notes'] ?? $tracking->notes,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'action' => 'check_out',
                    'tracking_id' => $tracking->id,
                    'label' => $tracking->getTrackableLabel(),
                    'duration_days' => $tracking->getDurationInDays(),
                    'cost' => $tracking->getImmobilizationCost(),
                ]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Equipment scan failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'enregistrement.',
            ], 500);
        }
    }
}
