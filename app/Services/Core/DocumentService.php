<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
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
     * @return string Le chemin absolu vers le fichier PDF généré.
     */
    public function generate(string $view, array $data, string $filename, string $type, string $position = 'portait', bool $pdfView = false): string
    {
        $html = View::make($view, $data)->render();

        $browsershot = Browsershot::html($html)
            ->setNodeBinary(config('browsershot.node_binary_path'))
            ->setNpmBinary(config('browsershot.npm_binary_path'))
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->noSandbox();
            
        if ($position !== 'portait') {
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

        Storage::disk('public')->put($relativePath, $pdfContent);

        if ($pdfView) {
            return $pdfContent;
        }

        return Storage::disk('public')->path($relativePath);
    }
}
