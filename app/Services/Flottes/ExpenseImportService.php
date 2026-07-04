<?php

namespace App\Services\Flottes;

use App\Enums\Flottes\FleetExpenseType;
use App\Models\Flottes\FleetExpense;
use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseImportService
{
    /**
     * Importe un fichier CSV générique et créé les FuelTransaction ou FleetExpense.
     * Le CSV doit avoir un point-virgule comme séparateur (standard EU) ou virgule.
     */
    public function importFromCsv(string $filePath, array $columnMapping, string $delimiter = ';'): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new Exception("Fichier CSV introuvable ou illisible.");
        }

        $results = [
            'success' => 0,
            'errors' => [],
        ];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, $delimiter);
            $rowIndex = 1;

            if (! $headers) {
                throw new Exception("Le fichier CSV est vide ou mal formaté.");
            }

            DB::beginTransaction();

            try {
                while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                    $rowIndex++;
                    if (count($headers) !== count($data)) {
                        $results['errors'][] = "Ligne {$rowIndex} : Mauvais nombre de colonnes.";
                        continue;
                    }
                    $row = array_combine($headers, $data);

                    try {
                        $this->processRow($row, $columnMapping);
                        $results['success']++;
                    } catch (\Throwable $e) {
                        $results['errors'][] = "Ligne {$rowIndex} : " . $e->getMessage();
                    }
                }
                
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            } finally {
                fclose($handle);
            }
        }

        return $results;
    }

    /**
     * Traite une ligne selon le mapping de colonnes fourni.
     * 
     * $columnMapping = [
     *    'license_plate' => 'Immatriculation',
     *    'date' => 'Date_Transaction',
     *    'amount_ttc' => 'Montant_TTC',
     *    'amount_ht' => 'Montant_HT',
     *    'merchant' => 'Station_Service',
     *    'type' => 'Type_Produit', // Carburant, Peage, Parking, Lavage
     *    'liters' => 'Volume', // Pour carburant
     *    'odometer' => 'Kilometrage',
     * ]
     */
    protected function processRow(array $row, array $columnMapping): void
    {
        $plate = $this->getValue($row, $columnMapping, 'license_plate');
        if (! $plate) {
            throw new Exception("Plaque d'immatriculation manquante.");
        }

        $formattedPlate = str_replace(['-', ' '], '', strtoupper(trim($plate)));
        $vehicle = Vehicle::where('license_plate', $formattedPlate)->first();
        if (! $vehicle) {
            throw new Exception("Véhicule introuvable pour la plaque : {$plate}");
        }

        $dateStr = $this->getValue($row, $columnMapping, 'date');
        $date = $dateStr ? \Carbon\Carbon::parse($dateStr) : now();

        $type = strtolower($this->getValue($row, $columnMapping, 'type') ?? '');
        $merchant = $this->getValue($row, $columnMapping, 'merchant') ?? 'Inconnu';
        
        $ttcStr = $this->getValue($row, $columnMapping, 'amount_ttc');
        $amountTtc = $ttcStr ? (float) str_replace(',', '.', (string) $ttcStr) : 0;
        
        $htStr = $this->getValue($row, $columnMapping, 'amount_ht');
        $amountHt = $htStr ? (float) str_replace(',', '.', (string) $htStr) : $amountTtc;
        
        $odoStr = $this->getValue($row, $columnMapping, 'odometer');
        $odometer = $odoStr ? (float) str_replace(',', '.', (string) $odoStr) : 0;

        // Trouver le conducteur actuel au moment de la transaction
        $assignment = $vehicle->assignments()
            ->where('start_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
            })->first();

        $employeeId = $assignment ? $assignment->employee_id : null;

        if (str_contains($type, 'carburant') || str_contains($type, 'gasoil') || str_contains($type, 'sp95')) {
            // C'est du carburant -> FuelTransaction
            $litersStr = $this->getValue($row, $columnMapping, 'liters');
            $liters = $litersStr ? (float) str_replace(',', '.', (string) $litersStr) : 0;
            
            FuelTransaction::create([
                'vehicle_id' => $vehicle->id,
                'employee_id' => $employeeId,
                'liters' => $liters,
                'cost_ht' => $amountHt,
                'odometer' => $odometer > 0 ? $odometer : $vehicle->odometer,
                'purchased_at' => $date,
                'station_name' => $merchant,
            ]);

            // Mettre à jour l'odomètre du véhicule si c'est plus récent
            if ($odometer > $vehicle->odometer) {
                $vehicle->update(['odometer' => $odometer]);
            }
        } else {
            // C'est une autre dépense -> FleetExpense
            $expenseType = FleetExpenseType::OTHER;
            if (str_contains($type, 'peage') || str_contains($type, 'péage')) {
                $expenseType = FleetExpenseType::PEAGE;
            } elseif (str_contains($type, 'parking')) {
                $expenseType = FleetExpenseType::PARKING;
            } elseif (str_contains($type, 'lavage')) {
                $expenseType = FleetExpenseType::WASH;
            }

            $vatRate = \App\Models\Core\VatRate::where('is_default', true)->first() 
                ?? \App\Models\Core\VatRate::first();

            FleetExpense::create([
                'vehicle_id' => $vehicle->id,
                'employee_id' => $employeeId,
                'type' => $expenseType,
                'amount_ht' => $amountHt,
                'amount_ttc' => $amountTtc,
                'vat_rate_id' => $vatRate?->id,
                'merchant_name' => $merchant,
                'description' => "Import automatique",
                'spent_at' => $date,
            ]);
        }
    }

    protected function getValue(array $row, array $mapping, string $key): ?string
    {
        if (! isset($mapping[$key])) {
            return null;
        }

        $csvColumn = $mapping[$key];
        return $row[$csvColumn] ?? null;
    }
}
