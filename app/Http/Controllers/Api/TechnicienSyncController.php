<?php

namespace App\Http\Controllers\Api;

use App\Enums\Articles\ItemType;
use App\Enums\Core\UnitType;
use App\Enums\Interventions\InterventionStatus;
use App\Http\Controllers\Controller;
use App\Models\Articles\Item;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionGpsTrack;
use App\Models\Interventions\InterventionMaterial;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechnicienSyncController extends Controller
{
    /**
     * Get interventions for the authenticated technician.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $salarieId = $user->salarie?->id;

        $query = Intervention::with([
            'thirdParty:id,name,address,city,zip_code,phone',
            'chantier:id,name,reference,address,city',
            'materials:id,intervention_id,name,quantity,price',
        ])->where('status', '!=', InterventionStatus::BROUILLON->value);

        if ($salarieId) {
            $query->whereHas('workers', function ($q) use ($salarieId) {
                $q->where('employee_id', $salarieId);
            });
        }

        $interventions = $query->get();

        return response()->json([
            'data' => $interventions,
        ]);
    }

    /**
     * Sync local changes back to the server.
     */
    public function sync(Request $request)
    {
        $user = $request->user();
        $salarieId = $user->salarie?->id;

        if (! $salarieId) {
            return response()->json(['error' => 'User is not a valid technician.'], 403);
        }

        $operations = $request->input('operations', []);
        $processed = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($operations as $operation) {
                if (! isset($operation['type'], $operation['payload'])) {
                    continue;
                }

                $type = $operation['type'];
                $payload = $operation['payload'];

                // Verify the intervention belongs to the technician
                $interventionId = $payload['intervention_id'] ?? null;
                if (! $interventionId) {
                    $failed++;

                    continue;
                }

                $intervention = Intervention::whereHas('workers', function ($q) use ($salarieId) {
                    $q->where('employee_id', $salarieId);
                })->find($interventionId);

                if (! $intervention) {
                    $failed++;

                    continue;
                }

                if ($type === 'UPDATE_STATUS') {
                    // Make sure string matches enum
                    $statusValue = match (strtoupper($payload['status'])) {
                        'EN_COURS' => InterventionStatus::EN_COURS,
                        'TERMINEE' => InterventionStatus::TERMINEE,
                        'ANNULEE' => InterventionStatus::ANNULEE,
                        default => InterventionStatus::PLANIFIEE
                    };
                    $intervention->status = $statusValue;
                    if (isset($payload['completed_at'])) {
                        $intervention->completed_at = Carbon::parse($payload['completed_at']);
                    }
                    $intervention->save();
                    $processed++;
                } elseif ($type === 'ADD_MATERIAL') {
                    if (! isset($payload['name']) || ! is_string($payload['name']) || trim($payload['name']) === '') {
                        $failed++;

                        continue;
                    }
                    if (! isset($payload['quantity']) || ! is_numeric($payload['quantity']) || $payload['quantity'] <= 0) {
                        $failed++;

                        continue;
                    }

                    $payload['name'] = trim($payload['name']);

                    $unit = Unit::firstOrCreate(['symbol' => 'U'], ['name' => 'Unité', 'type' => UnitType::UNIT]);
                    $vat = VatRate::firstOrCreate(['rate' => 20], ['name' => 'TVA 20%']);

                    $item = Item::firstOrCreate(
                        ['name' => $payload['name']],
                        [
                            'reference' => 'OFFLINE-'.uniqid(),
                            'type' => ItemType::CONSUMABLE,
                            'purchase_price' => 0,
                            'selling_price' => $payload['price'] ?? 0,
                            'is_active' => true,
                            'unit_id' => $unit->id,
                            'vat_rate_id' => $vat->id,
                        ]
                    );

                    InterventionMaterial::create([
                        'intervention_id' => $intervention->id,
                        'item_id' => $item->id,
                        'quantity' => $payload['quantity'],
                        'selling_price' => $payload['price'] ?? 0,
                    ]);
                    $processed++;
                } elseif ($type === 'UPDATE_GPS') {
                    $latitude = $payload['latitude'] ?? null;
                    $longitude = $payload['longitude'] ?? null;
                    $recordedAt = $payload['recorded_at'] ?? null;

                    if ($latitude === null || $longitude === null || ! is_numeric($latitude) || ! is_numeric($longitude) || $recordedAt === null) {
                        $failed++;

                        continue;
                    }

                    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                        $failed++;

                        continue;
                    }

                    InterventionGpsTrack::create([
                        'intervention_id' => $intervention->id,
                        'employee_id' => $salarieId,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'altitude' => $payload['altitude'] ?? null,
                        'accuracy' => $payload['accuracy'] ?? null,
                        'speed' => $payload['speed'] ?? null,
                        'heading' => $payload['heading'] ?? null,
                        'recorded_at' => $recordedAt,
                    ]);

                    $intervention->update([
                        'last_latitude' => $latitude,
                        'last_longitude' => $longitude,
                        'last_gps_at' => $recordedAt,
                    ]);

                    $processed++;
                } elseif ($type === 'UPLOAD_PHOTO') {
                    // Assuming base64 image
                    if (isset($payload['image'])) {
                        $intervention->addMediaFromBase64($payload['image'])
                            ->usingFileName('photo_'.time().'.jpg')
                            ->toMediaCollection('photos');
                        $processed++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'processed' => $processed,
                'failed' => $failed,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Technicien Sync Failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'An error occurred during synchronization.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
