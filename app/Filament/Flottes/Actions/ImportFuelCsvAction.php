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
                FileUpload::make('csv_file')
                    ->label('Fichier CSV (Standard Batistack)')
                    ->helperText('Colonnes attendues : Immatriculation, Date (Y-m-d H:i), Litres, MontantHT, Odomètre, Station')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->required()
                    ->storeFiles(false),
            ])
            ->action(function (array $data, Action $action) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $data['csv_file'];
                $path = $file->getRealPath();
                
                $fileHandle = fopen($path, 'r');
                if ($fileHandle === false) {
                    Notification::make()->title('Erreur de lecture du fichier')->danger()->send();
                    return;
                }

                $header = fgetcsv($fileHandle, 1000, ';'); // Support point-virgule (courant en FR)
                if (!$header || count($header) < 6) {
                    $header = fgetcsv($fileHandle, 1000, ','); // Fallback sur virgule
                }

                $successCount = 0;
                $errorCount = 0;
                
                $fuelService = app(VehicleFuelService::class);

                while (($row = fgetcsv($fileHandle, 1000, ';')) !== false) {
                    if (count($row) < 6) {
                        $row = explode(',', implode(';', $row)); // fallback
                    }
                    if (count($row) < 6) continue;

                    // Mapping attendu : 0: Immatriculation, 1: Date, 2: Litres, 3: MontantHT, 4: Odomètre, 5: Station
                    $licensePlate = trim($row[0]);
                    $dateStr = trim($row[1]);
                    $liters = (float) str_replace(',', '.', trim($row[2]));
                    $costHt = (float) str_replace(',', '.', trim($row[3]));
                    $odometer = (float) str_replace(',', '.', trim($row[4]));
                    $station = trim($row[5]);

                    $vehicle = Vehicle::where('license_plate', $licensePlate)->first();

                    if (!$vehicle) {
                        $errorCount++;
                        continue;
                    }

                    try {
                        $date = Carbon::parse($dateStr);
                        
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
