<?php

namespace App\Console\Commands\Flottes;

use App\Models\Flottes\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportFleetDataCommand extends Command
{
    protected $signature = 'flottes:export
                            {--format=csv : Format export (csv/json)}
                            {--type=vehicles : Type données (vehicles/assignments/fines)}';

    protected $description = 'Exporte données flotte en CSV ou JSON';

    public function handle(): int
    {
        $this->info('=== Export Données Flotte ===');

        $format = $this->option('format');
        $type = $this->option('type');

        if ($format === 'csv' && ! in_array($type, ['vehicles'], true)) {
            $this->error("Le type '{$type}' n'est pas encore supporté en CSV.");

            return self::FAILURE;
        }

        $filename = $this->getFilename($type, $format);
        $path = "exports/{$filename}";

        $this->line("📤 Export en cours : {$filename}");

        $data = $this->collectData($type);

        if ($format === 'csv') {
            $this->exportCsv($path, $data, $type);
        } else {
            $this->exportJson($path, $data);
        }

        $this->info("✅ Export complété : storage/{$path}");

        return self::SUCCESS;
    }

    protected function collectData(string $type): array
    {
        return match ($type) {
            'vehicles' => Vehicle::with(['assignments', 'maintenances', 'fines'])->get()->toArray(),
            'assignments' => Vehicle::with('assignments.employee', 'assignments.chantier')->get()->toArray(),
            'fines' => Vehicle::with('fines.employee')->get()->toArray(),
            default => [],
        };
    }

    protected function exportCsv(string $path, array $data, string $type): void
    {
        $file = fopen('php://memory', 'r+');

        if ($type === 'vehicles') {
            fputcsv($file, ['Référence', 'Plaque', 'Marque', 'Modèle', 'Statut', 'Kilométrage', 'TCO']);

            foreach ($data as $vehicle) {
                fputcsv($file, [
                    $vehicle['reference'],
                    $vehicle['license_plate'],
                    $vehicle['brand'],
                    $vehicle['model'],
                    $vehicle['status'],
                    $vehicle['odometer'],
                    $vehicle['tco_cache'],
                ]);
            }
        }

        rewind($file);
        $csv = stream_get_contents($file);
        fclose($file);

        Storage::put($path, $csv);
    }

    protected function exportJson(string $path, array $data): void
    {
        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function getFilename(string $type, string $format): string
    {
        return "flotte_{$type}_".now()->format('Y-m-d_H-i-s').".{$format}";
    }
}
