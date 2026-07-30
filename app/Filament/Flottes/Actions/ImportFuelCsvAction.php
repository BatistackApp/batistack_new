<?php

namespace App\Filament\Flottes\Actions;

use App\Models\Flottes\Vehicle;
use App\Services\Flottes\VehicleFuelService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ImportFuelCsvAction
{
    public static function make(): Action
    {
        return Action::make('import_fuel_csv')
            ->label('Importer Factures CSV (Carburant)')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->form([
                \Filament\Forms\Components\Select::make('format')
                    ->label('Format du Fournisseur')
                    ->options([
                        'standard' => 'Standard Batistack',
                        'totalenergies' => 'TotalEnergies (CSV)',
                        'dkv' => 'DKV Euro Service (CSV)',
                    ])
                    ->default('standard')
                    ->required(),
                FileUpload::make('csv_file')
                    ->label('Fichier CSV')
                    ->helperText('Sélectionnez le format correspondant au fournisseur.')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->required()
                    ->storeFiles(false),
            ])
            ->action(function (array $data, Action $action) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $data['csv_file'];
                $path = $file->getRealPath();
                $format = $data['format'] ?? 'standard';
                
                $fileHandle = fopen($path, 'r');
                if ($fileHandle === false) {
                    Notification::make()->title('Erreur de lecture du fichier')->danger()->send();
                    return;
                }

                $header = fgetcsv($fileHandle, 1000, ';'); // Support point-virgule
                if (!$header || count($header) < 6) {
                    $header = fgetcsv($fileHandle, 1000, ','); // Fallback sur virgule
                }

                $successCount = 0;
                $errorCount = 0;
                
                $fuelService = app(VehicleFuelService::class);

                while (($row = fgetcsv($fileHandle, 1000, ';')) !== false) {
                    if (count($row) < 5) {
                        $row = explode(',', implode(';', $row)); // fallback
                    }
                    if (count($row) < 5) continue;

                    try {
                        $licensePlate = '';
                        $dateStr = '';
                        $liters = 0.0;
                        $costHt = 0.0;
                        $odometer = 0.0;
                        $station = '';
                        $date = null;

                        if ($format === 'totalenergies') {
                            // TotalEnergies mock format
                            // 0: Carte, 1: Immatriculation, 2: Date (d/m/Y), 3: Heure (H:i), 4: Produit, 5: Quantite, 6: Montant HT, 7: Kilometrage, 8: Station
                            $licensePlate = trim($row[1] ?? '');
                            $dateStr = trim($row[2] ?? '') . ' ' . trim($row[3] ?? '');
                            $liters = (float) str_replace(',', '.', trim($row[5] ?? '0'));
                            $costHt = (float) str_replace(',', '.', trim($row[6] ?? '0'));
                            $odometer = (float) str_replace(',', '.', trim($row[7] ?? '0'));
                            $station = trim($row[8] ?? '');
                            $date = Carbon::createFromFormat('d/m/Y H:i', $dateStr);
                        } elseif ($format === 'dkv') {
                            // DKV mock format
                            // 0: Card Number, 1: Plate, 2: Date/Time (d/m/Y H:i), 3: Product, 4: Quantity, 5: Net Amount, 6: Odometer, 7: Station Name
                            $licensePlate = trim($row[1] ?? '');
                            $dateStr = trim($row[2] ?? '');
                            $liters = (float) str_replace(',', '.', trim($row[4] ?? '0'));
                            $costHt = (float) str_replace(',', '.', trim($row[5] ?? '0'));
                            $odometer = (float) str_replace(',', '.', trim($row[6] ?? '0'));
                            $station = trim($row[7] ?? '');
                            $date = Carbon::createFromFormat('d/m/Y H:i', $dateStr);
                        } else {
                            // Standard Batistack format
                            // 0: Immatriculation, 1: Date, 2: Litres, 3: MontantHT, 4: Odomètre, 5: Station
                            $licensePlate = trim($row[0] ?? '');
                            $dateStr = trim($row[1] ?? '');
                            $liters = (float) str_replace(',', '.', trim($row[2] ?? '0'));
                            $costHt = (float) str_replace(',', '.', trim($row[3] ?? '0'));
                            $odometer = (float) str_replace(',', '.', trim($row[4] ?? '0'));
                            $station = trim($row[5] ?? '');
                            $date = Carbon::parse($dateStr);
                        }

                        $vehicle = Vehicle::where('license_plate', $licensePlate)->first();

                        if (!$vehicle) {
                            $errorCount++;
                            continue;
                        }
                        
                        $fuelService->processAndAuditFuelTransaction(
                            $vehicle,
                            $liters,
                            $costHt,
                            $odometer,
                            $date,
                            $station
                        );
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                    }
                }

                fclose($fileHandle);

                if ($successCount > 0) {
                    Notification::make()
                        ->title("Import réussi")
                        ->body("{$successCount} transactions importées avec succès." . ($errorCount > 0 ? " ({$errorCount} erreurs ignorées)" : ""))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title("Échec de l'import")
                        ->body("Aucune transaction n'a pu être importée. Vérifiez le format du fichier.")
                        ->danger()
                        ->send();
                }
            });
    }
}
