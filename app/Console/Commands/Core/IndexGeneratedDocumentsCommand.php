<?php

namespace App\Console\Commands\Core;

use App\Models\Core\GeneratedDocument;
use App\Services\Core\DocumentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class IndexGeneratedDocumentsCommand extends Command
{
    protected $signature = 'core:index-generated-documents {--module= : Filtrer par module} {--force : Réindexer tous les documents, y compris déjà indexés}';

    protected $description = 'Scanne le disque et indexe les documents PDF générés par DocumentService';

    private const MODULE_MAP = [
        'articles' => 'articles',
        'chantiers' => 'chantiers',
        'commerce' => 'commerce',
        'flotte' => 'flottes',
        'gpao' => 'gpao',
        'immobilisations' => 'immobilisations',
        'interventions' => 'interventions',
        'rh' => 'rh',
        'tiers' => 'tiers',
    ];

    public function handle(): int
    {
        $disk = DocumentService::getDisk();
        $moduleFilter = $this->option('module');
        $force = $this->option('force');

        $this->info("Scan du disque \"{$disk}\"...");

        $directories = Storage::disk($disk)->directories('documents');

        if (empty($directories)) {
            $this->warn('Aucun dossier trouvé dans documents/');

            return self::SUCCESS;
        }

        $totalIndexed = 0;
        $totalSkipped = 0;

        foreach ($directories as $directory) {
            $module = basename($directory);

            if ($module === 'doe_temp') {
                continue;
            }

            $mappedModule = self::MODULE_MAP[$module] ?? $module;

            if ($moduleFilter && $mappedModule !== $moduleFilter) {
                continue;
            }

            $this->line("  Module: {$mappedModule}");

            $files = $this->getAllPdfFiles($disk, $directory);

            foreach ($files as $filePath) {
                $fileName = basename($filePath, '.pdf');
                $relativePath = ltrim($filePath, '/');

                if (! $force) {
                    $exists = GeneratedDocument::where('file_path', $relativePath)->exists();
                    if ($exists) {
                        $totalSkipped++;

                        continue;
                    }
                }

                $subType = $this->extractSubType($filePath);
                $fileSize = Storage::disk($disk)->size($relativePath);

                GeneratedDocument::create([
                    'module' => $mappedModule,
                    'type' => $subType,
                    'entity_type' => null,
                    'entity_id' => null,
                    'file_path' => $relativePath,
                    'file_disk' => $disk,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'generated_by' => null,
                    'generated_at' => Storage::disk($disk)->lastModified($relativePath),
                ]);

                $totalIndexed++;
            }
        }

        $this->info("Indexation terminée : {$totalIndexed} documents indexés, {$totalSkipped} ignorés.");

        return self::SUCCESS;
    }

    private function getAllPdfFiles(string $disk, string $directory): \Generator
    {
        $storage = Storage::disk($disk);

        foreach ($storage->allFiles($directory) as $file) {
            if (str_ends_with($file, '.pdf')) {
                yield $file;
            }
        }
    }

    private function extractSubType(string $filePath): string
    {
        $parts = explode('/', $filePath);

        // documents/module/subtype/filename.pdf → subtype
        if (count($parts) >= 4) {
            return $parts[count($parts) - 2];
        }

        // documents/module/filename.pdf → module
        if (count($parts) >= 3) {
            return $parts[count($parts) - 3];
        }

        return 'other';
    }
}
