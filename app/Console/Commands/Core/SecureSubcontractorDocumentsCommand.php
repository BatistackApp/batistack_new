<?php

namespace App\Console\Commands\Core;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\ThirdParty;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SecureSubcontractorDocumentsCommand extends Command
{
    protected $signature = 'subcontractors:secure-documents {--dry-run : Afficher les migrations sans déplacer les fichiers}';

    protected $description = 'Déplace les documents de sous-traitants des disques publics vers le disque privé local';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $movedDocuments = 0;
        $movedInvoices = 0;
        $failed = 0;

        $subcontractorIds = ThirdParty::query()
            ->where('type', ThirdPartyType::SUBCONTRACTOR)
            ->pluck('id');

        GeneratedDocument::query()
            ->where('entity_type', ThirdParty::class)
            ->whereIn('entity_id', $subcontractorIds)
            ->where('file_disk', '!=', 'local')
            ->orderBy('id')
            ->chunkById(100, function ($documents) use ($dryRun, &$movedDocuments, &$failed): void {
                foreach ($documents as $document) {
                    if ($dryRun) {
                        $this->line("[simulation] Document {$document->id}: {$document->file_disk}:{$document->file_path} → local");
                        $movedDocuments++;

                        continue;
                    }

                    if (! $this->copyToPrivateDisk($document->file_disk, $document->file_path)) {
                        $this->error("Échec du déplacement du document {$document->id}.");
                        $failed++;

                        continue;
                    }

                    $oldDisk = $document->file_disk;
                    $oldPath = $document->file_path;

                    GeneratedDocument::query()
                        ->where('file_disk', $oldDisk)
                        ->where('file_path', $oldPath)
                        ->update(['file_disk' => 'local']);

                    if (! GeneratedDocument::query()->where('file_disk', $oldDisk)->where('file_path', $oldPath)->exists()) {
                        Storage::disk($oldDisk)->delete($oldPath);
                    }

                    $movedDocuments++;
                }
            });

        SubcontractorSituation::query()
            ->whereIn('subcontractor_id', $subcontractorIds)
            ->with('media')
            ->orderBy('id')
            ->chunkById(100, function ($situations) use ($dryRun, &$movedInvoices, &$failed): void {
                foreach ($situations as $situation) {
                    foreach ($situation->getMedia('invoice_document') as $media) {
                        $oldDisk = $media->disk;
                        $path = $media->getPathRelativeToRoot();

                        if ($oldDisk === 'local') {
                            continue;
                        }

                        if ($dryRun) {
                            $this->line("[simulation] Facture {$situation->id}, média {$media->id}: {$oldDisk}:{$path} → local");
                            $movedInvoices++;

                            continue;
                        }

                        if (! $this->copyToPrivateDisk($oldDisk, $path)) {
                            $this->error("Échec du déplacement du média {$media->id}.");
                            $failed++;

                            continue;
                        }

                        $media->disk = 'local';
                        $media->save();

                        Storage::disk($oldDisk)->delete($path);

                        $movedInvoices++;
                    }
                }
            });

        $this->info(($dryRun ? 'Simulation terminée' : 'Migration terminée').": {$movedDocuments} document(s), {$movedInvoices} facture(s), {$failed} erreur(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function copyToPrivateDisk(string $sourceDisk, string $path): bool
    {
        $source = Storage::disk($sourceDisk);
        $target = Storage::disk('local');

        if (! $source->exists($path)) {
            $this->warn("Fichier source introuvable : {$sourceDisk}:{$path}");

            return false;
        }

        if ($target->exists($path)) {
            $this->warn("Le fichier existe déjà sur le disque privé : local:{$path}");

            return false;
        }

        $stream = $source->readStream($path);
        if (! is_resource($stream)) {
            return false;
        }

        try {
            return $target->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }
    }
}
