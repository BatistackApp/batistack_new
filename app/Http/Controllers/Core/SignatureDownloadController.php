<?php

namespace App\Http\Controllers\Core;

use App\Enums\Core\SignatureStatus;
use App\Http\Controllers\Controller;
use App\Models\Core\Signature;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SignatureDownloadController extends Controller
{
    /**
     * Télécharge le PDF tamponné (signé) associé à une Signature.
     */
    public function download(Signature $signature): BinaryFileResponse
    {
        abort_unless($signature->status === SignatureStatus::SIGNED, 403);

        $signable = $signature->signable;
        abort_unless($signable, 404);

        $path = $signable->getStampedDocumentPath($signature);
        abort_unless($path && file_exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return response()->download($path, 'document_signe_'.$signature->token.'.'.$extension);
    }
}
