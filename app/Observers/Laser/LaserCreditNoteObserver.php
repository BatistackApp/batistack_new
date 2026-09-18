<?php

namespace App\Observers\Laser;

use App\Models\Laser\LaserCreditNote;
use App\Services\Laser\LaserDocumentationService;
use Illuminate\Support\Facades\Storage;

class LaserCreditNoteObserver
{
    public function __construct(
        protected LaserDocumentationService $documentService,
    ) {}

    public function deleted(LaserCreditNote $creditNote): void
    {
        $disk = $this->documentService::getDisk();
        Storage::disk($disk)->delete($this->documentService->getCreditNotePath($creditNote));
    }
}
