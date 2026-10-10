<?php

namespace App\Http\Controllers\Subcontractor;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\ThirdParty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateDocumentController
{
    public function previewGenerated(Request $request, int $document): StreamedResponse
    {
        $record = $this->findGeneratedDocumentForCurrentSubcontractor($request, $document);

        return $this->streamFromDisk(
            $record->file_disk,
            $record->file_path,
            $record->file_name,
            'inline'
        );
    }

    public function previewGeneratedForAdmin(int $document): StreamedResponse
    {
        $record = GeneratedDocument::query()->findOrFail($document);

        return $this->streamFromDisk(
            $record->file_disk,
            $record->file_path,
            $record->file_name,
            'inline'
        );
    }

    public function downloadGenerated(Request $request, int $document): StreamedResponse
    {
        $record = $this->findGeneratedDocumentForCurrentSubcontractor($request, $document);

        return $this->streamFromDisk(
            $record->file_disk,
            $record->file_path,
            $record->file_name,
            'attachment'
        );
    }

    public function downloadInvoice(Request $request, int $situation): StreamedResponse
    {
        $thirdParty = $this->currentSubcontractor($request);
        $record = SubcontractorSituation::query()
            ->whereKey($situation)
            ->where('subcontractor_id', $thirdParty->id)
            ->firstOrFail();

        $media = $record->getFirstMedia('invoice_document');
        abort_unless($media, 404);

        $diskName = $media->disk;
        $path = $media->getPathRelativeToRoot();

        return $this->streamFromDisk($diskName, $path, $media->file_name, 'attachment');
    }

    private function findGeneratedDocumentForCurrentSubcontractor(Request $request, int $document): GeneratedDocument
    {
        $thirdParty = $this->currentSubcontractor($request);

        return GeneratedDocument::query()
            ->whereKey($document)
            ->where('entity_type', ThirdParty::class)
            ->where('entity_id', $thirdParty->id)
            ->firstOrFail();
    }

    private function currentSubcontractor(Request $request): ThirdParty
    {
        $user = $request->user();
        abort_unless($user, 403);

        $thirdParty = $user->contact?->thirdParty;
        abort_unless($thirdParty && $thirdParty->type === ThirdPartyType::SUBCONTRACTOR, 403);

        return $thirdParty;
    }

    private function streamFromDisk(string $diskName, string $path, string $filename, string $disposition): StreamedResponse
    {
        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($path), 404);

        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404);

        $safeFilename = str_replace(['"', "\r", "\n"], '', basename($filename));
        if (! str_ends_with(strtolower($safeFilename), '.pdf')) {
            $safeFilename .= '.pdf';
        }

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$safeFilename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
