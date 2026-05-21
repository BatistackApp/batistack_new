<?php

namespace App\Services\Core;

use Illuminate\Database\Eloquent\Model;
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
    protected function generate(string $view, array $data, string $filename, string $type, string $position = 'portait', bool $pdfView = false): string
    {
        $html = View::make($view, $data)->render();

        if ($position == 'portait') {
            $pdfContent = Browsershot::html($html)
                ->setNodeBinary(config('browsershot.node_binary_path'))
                ->setNpmBinary(config('browsershot.npm_binary_path'))
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->noSandbox()
                ->pdf();
        } else {
            $pdfContent = Browsershot::html($html)
                ->setNodeBinary(config('browsershot.node_binary_path'))
                ->setNpmBinary(config('browsershot.npm_binary_path'))
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->landscape()
                ->waitUntilNetworkIdle()
                ->noSandbox()
                ->pdf();
        }

        $basePath = storage_path('app/public/documents/'.$type.'/');

        if (! file_exists($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $path = "{$basePath}{$filename}.pdf";
        file_put_contents($path, $pdfContent);

        if ($pdfView) {
            return $pdfContent;
        }

        return $path;
    }
}
