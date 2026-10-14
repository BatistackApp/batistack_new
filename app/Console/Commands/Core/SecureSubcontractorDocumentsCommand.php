<?php

namespace App\Console\Commands\Core;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\ThirdParty;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

                    if (! GeneratedDocument::query()->where('file_disk', $oldDisk)->where('file_path', $oldPath)->exists()) {
                        Storage::disk($oldDisk)->delete($oldPath);
                    } else {
                        $this->warn("Le fichier source {$oldDisk}:{$oldPath} reste utilisé par un autre document indexé.");
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

                        // Keep any other media records that resolve to the same physical
                        // source path consistent before deleting that shared public file.
                        Media::query()
                            ->where('disk', $oldDisk)
                            ->whereKeyNot($media->getKey())
                            ->chunkById(100, function ($otherMediaRecords) use ($path): void {
                                foreach ($otherMediaRecords as $otherMedia) {
                                    if ($otherMedia->getPathRelativeToRoot() !== $path) {
                                        continue;
                                    }

                                    $otherMedia->disk = 'local';
                                    $otherMedia->save();
                                }
                            });

                        Storage::disk($oldDisk)->delete($path);

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

        return 'subcontractors/'.$subcontractorId.'/documents/'.$document->module.'/'.$document->type.'/'.substr(hash('sha256', $document->file_path), 0, 12).'/'.basename($document->file_path);
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

        $sourceHash = $this->streamSha256($source, $path);
        if ($sourceHash === null) {
            $this->warn("Impossible de calculer l'empreinte du fichier source : {$sourceDisk}:{$path}");

            return false;
        }

        // A previous run may have copied the file before interruption. Accept it
        // only when the private copy is byte-for-byte identical.
        if ($target->exists($targetPath)) {
            $targetHash = $this->streamSha256($target, $targetPath);

            if ($targetHash !== null && hash_equals($sourceHash, $targetHash)) {
                return true;
            }

            $this->warn("Le fichier privé existe déjà mais son empreinte diffère : local:{$targetPath}");

            return false;
        }

        $stream = $source->readStream($path);
        if (! is_resource($stream)) {
            return false;
        }

        try {
            if (! $target->writeStream($targetPath, $stream)) {
                $target->delete($targetPath);

                return false;
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $targetHash = $this->streamSha256($target, $targetPath);
        if ($targetHash === null || ! hash_equals($sourceHash, $targetHash)) {
            $target->delete($targetPath);
            $this->warn("Vérification SHA-256 échouée après copie : local:{$targetPath}");

            return false;
        }

        return true;
    }

    private function streamSha256($disk, string $path): ?string
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            return null;
        }

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }
}
