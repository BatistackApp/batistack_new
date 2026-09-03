<?php

namespace App\Services\Core;

use App\Models\Core\GeneratedDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class DocumentService
{
    /**
     * Génère un document PDF à partir d'une vue Blade et l'enregistre sur le disque public.
     *
     * @param  string  $view  Le nom de la vue Blade à rendre.
     * @param  array  $data  Les données à passer à la vue.
     * @param  string  $filename  Le nom du fichier de sortie (sans extension).
     * @param  string  $type  Le sous-répertoire dans lequel stocker le document.
     * @param  bool  $pdfView  Si true, retourne le contenu brut du PDF au lieu du chemin.
     * @param  Model|null  $entity  L'entité Eloquent liée au document (optionnel).
     * @param  string|null  $documentType  Le type de document pour l'index (ex: 'devis', 'facture').
     * @return string Le chemin relatif vers le fichier PDF généré ou le contenu brut.
     */
    public static function getDisk(): string
    {
        return env('DOCUMENTS_DISK', 'public');
    }

    public function download(string $relativePath, ?string $filename = null)
    {
        return Storage::disk(static::getDisk())->download($relativePath, $filename);
    }

    public function generate(
        string $view,
        array $data,
        string $filename,
        string $type = 'other',
        bool $pdfView = false,
        ?Model $entity = null,
        ?string $documentType = null
    ): mixed {
        $browsershot = Browsershot::html(view($view, $data)->render())
            ->setNodeBinary(config('browsershot.node_binary_path'))
            ->setNpmBinary(config('browsershot.npm_binary_path'))
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->noSandbox();

        if (isset($data['paperSize'])) {
            $browsershot->paperSize($data['paperSize']['width'], $data['paperSize']['height']);
        } else {
            $browsershot->format($data['format'] ?? 'A4');
        }

        if (isset($data['margins'])) {
            $browsershot->margins($data['margins']['top'] ?? 0, $data['margins']['right'] ?? 0, $data['margins']['bottom'] ?? 0, $data['margins']['left'] ?? 0);
        } else {
            $browsershot->margins(10, 10, 10, 10);
        }

        if (isset($data['position']) && $data['position'] !== 'portrait') {
            $browsershot->landscape();
        }

        if (config('browsershot.chrome_path') || env('BROWSERSHOT_CHROME_PATH')) {
            $chromePath = config('browsershot.chrome_path') ?: env('BROWSERSHOT_CHROME_PATH');
            $browsershot->setChromePath($chromePath);
        }

        // Set a writable cache directory for Puppeteer in case it needs to download Chrome
        $browsershot->setEnvironmentVariable('PUPPETEER_CACHE_DIR', storage_path('puppeteer'));

        $pdfContent = $browsershot->pdf();

        $relativePath = 'documents/'.$type.'/'.$filename.'.pdf';
        $disk = static::getDisk();

        Storage::disk($disk)->put($relativePath, $pdfContent);

        // Indexer le document généré
        $this->indexDocument($relativePath, $disk, $filename, $type, $entity, $documentType);

        if ($pdfView) {
            return $pdfContent;
        }

        return $relativePath;
    }

    /**
     * Indexe un document généré dans la table generated_documents.
     */
    protected function indexDocument(
        string $relativePath,
        string $disk,
        string $filename,
        string $type,
        ?Model $entity,
        ?string $documentType
    ): void {
        try {
            $fileSize = Storage::disk($disk)->size($relativePath);

            GeneratedDocument::updateOrCreate(
                ['file_path' => $relativePath],
                [
                    'module' => $this->extractModuleFromPath($type),
                    'type' => $documentType ?? $type,
                    'entity_type' => $entity ? get_class($entity) : null,
                    'entity_id' => $entity?->id,
                    'file_disk' => $disk,
                    'file_name' => $filename,
                    'file_size' => $fileSize,
                    'generated_by' => auth()->id(),
                    'generated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::error("DocumentService: échec d'indexation pour {$relativePath}: ".$e->getMessage());
        }
    }

    /**
     * Extrait le nom du module à partir du chemin de type.
     * Ex: 'commerce/quotes' → 'commerce', 'rh' → 'rh'.
     */
    protected function extractModuleFromPath(string $type): string
    {
        return explode('/', $type)[0];
    }
}
