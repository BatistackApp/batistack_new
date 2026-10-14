<?php

namespace App\Http\Controllers\Subcontractor;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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


    public function previewMedia(Request $request, int $media): StreamedResponse
    {
        $record = $this->findMediaForCurrentSubcontractor($request, $media);

        return $this->streamMedia($record, 'inline');
    }

    public function downloadMedia(Request $request, int $media): StreamedResponse
    {
        $record = $this->findMediaForCurrentSubcontractor($request, $media);

        return $this->streamMedia($record, 'attachment');
    }

    private function findMediaForCurrentSubcontractor(Request $request, int $mediaId): Media
    {
        $thirdParty = $this->currentSubcontractor($request);
        $media = Media::query()->findOrFail($mediaId);
        $model = $media->model;

        $belongsToSubcontractor = match (true) {
            $model instanceof ThirdParty => $model->is($thirdParty),
            $model instanceof ThirdPartyDocument => (int) $model->third_party_id === (int) $thirdParty->id,
            $model instanceof SubcontractorSituation => (int) $model->subcontractor_id === (int) $thirdParty->id,
            default => false,
        };

        abort_unless($belongsToSubcontractor, 404);
        // Never fall back to a public URL or stream a still-public document.
        abort_unless($media->disk === 'local', 404);

        return $media;
    }

    private function streamMedia(Media $media, string $disposition): StreamedResponse
    {
        $path = $media->getPathRelativeToRoot();
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($path), 404);

        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404);

        $filename = str_replace(["\"", "\r", "\n"], '', basename($media->file_name));

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function findGeneratedDocumentForCurrentSubcontractor(Request $request, int $document): GeneratedDocument
    {
        $thirdParty = $this->currentSubcontractor($request);

        return GeneratedDocument::query()
            ->whereKey($document)
            ->where(function ($query) use ($thirdParty): void {
                $query->where(function ($thirdPartyDocuments) use ($thirdParty): void {
                    $thirdPartyDocuments->where('entity_type', ThirdParty::class)
                        ->where('entity_id', $thirdParty->id);
                })->orWhere(function ($invoiceDocuments) use ($thirdParty): void {
                    $invoiceDocuments->where('entity_type', CustomerInvoice::class)
                        ->whereIn('entity_id', CustomerInvoice::query()
                            ->where('client_id', $thirdParty->id)
                            ->whereHas('client', fn ($query) => $query->where('type', ThirdPartyType::SUBCONTRACTOR))
                            ->select('id'));
                });
            })
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

        $safeFilename = str_replace(["\"", "\r", "\n"], '', basename($filename));
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
