<?php

namespace App\Console\Commands\Core;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\ThirdParty;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SecureSubcontractorDocumentsCommand extends Command
{
    protected $signature = 'subcontractors:secure-documents {--dry-run : Afficher les migrations sans déplacer les fichiers}';

    protected $description = 'Copie les documents de sous-traitants vers le disque privé local sans supprimer les sources';

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
            ->where(function ($query) use ($subcontractorIds): void {
                $query->where(function ($thirdPartyDocuments) use ($subcontractorIds): void {
                    $thirdPartyDocuments->where('entity_type', ThirdParty::class)
                        ->whereIn('entity_id', $subcontractorIds);
                })->orWhere(function ($invoiceDocuments) use ($subcontractorIds): void {
                    $invoiceDocuments->where('entity_type', CustomerInvoice::class)
                        ->whereIn('entity_id', CustomerInvoice::query()
                            ->whereIn('client_id', $subcontractorIds)
                            ->whereHas('client', fn ($query) => $query->where('type', ThirdPartyType::SUBCONTRACTOR))
                            ->select('id'));
                });
            })
            ->whereIn('file_disk', ['public', 's3-public'])
            ->orderBy('id')
            ->chunkById(100, function ($documents) use ($dryRun, &$movedDocuments, &$failed): void {
                foreach ($documents as $document) {
                    if ($dryRun) {
                        $targetPath = $this->privateGeneratedDocumentPath($document);
                        $this->line("[simulation] Document {$document->id}: {$document->file_disk}:{$document->file_path} → local:{$targetPath}");
                        $movedDocuments++;

                        continue;
                    }

                    $oldDisk = $document->file_disk;
                    $oldPath = $document->file_path;
                    $targetPath = $this->privateGeneratedDocumentPath($document);

                    if (! $this->copyToPrivateDisk($oldDisk, $oldPath, $targetPath)) {
                        $this->error("Échec du déplacement du document {$document->id}.");
                        $failed++;

                        continue;
                    }

                    $document->update([
                        'file_disk' => 'local',
                        'file_path' => $targetPath,
                    ]);

                    // Retain public source files until the private copy has been verified operationally.
                    // This makes the command safe to resume and avoids data loss from a partial/corrupt copy.
                    $this->warn("Source conservée pour vérification manuelle : {$oldDisk}:{$oldPath}");

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

                        if (! in_array($oldDisk, ['public', 's3-public'], true)) {
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

                        // Retain the public source until the private copy has been verified operationally.
                        $this->warn("Source conservée pour vérification manuelle : {$oldDisk}:{$path}");

                        $movedInvoices++;
                    }
                }
            });

        $this->info(($dryRun ? 'Simulation terminée' : 'Migration terminée').": {$movedDocuments} document(s), {$movedInvoices} facture(s), {$failed} erreur(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function privateGeneratedDocumentPath(GeneratedDocument $document): string
    {
        $subcontractorId = match ($document->entity_type) {
            ThirdParty::class => $document->entity_id,
            CustomerInvoice::class => CustomerInvoice::query()
                ->whereKey($document->entity_id)
                ->value('client_id'),
            default => null,
        };

        if (! $subcontractorId) {
            throw new \LogicException("Le document {$document->id} n'est pas lié à un sous-traitant.");
        }

        return 'subcontractors/'.$subcontractorId.'/documents/'.$document->module.'/'.$document->type.'/'.basename($document->file_path);
    }

    private function copyToPrivateDisk(string $sourceDisk, string $path, ?string $targetPath = null): bool
    {
        $targetPath ??= $path;
        $source = Storage::disk($sourceDisk);
        $target = Storage::disk('local');

        if (! $source->exists($path)) {
            $this->warn("Fichier source introuvable : {$sourceDisk}:{$path}");

            return false;
        }

        if ($target->exists($targetPath)) {
            $this->warn("Le fichier existe déjà sur le disque privé : local:{$targetPath}");

            return false;
        }

        $stream = $source->readStream($path);
        if (! is_resource($stream)) {
            return false;
        }

        try {
            return $target->writeStream($targetPath, $stream);
        } finally {
            fclose($stream);
        }
    }
}
