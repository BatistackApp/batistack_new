<?php

namespace App\Observers\Articles;

use App\Models\Articles\Item;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidBase64Data;

class BarcodeObserver
{
    /**
     * Génère un QR Code unique lors de la création d'un article ou d'une variante.
     */
    public function created(Item $item): void
    {
        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
        ]);

        // Le QR Code contient l'URL de la fiche ou la référence
        //$data = route('filament.articles.resources.items.view', $item->id);
        //$qrcode = (new QRCode($options))->render($data);

        // On stocke le SVG dans la collection media
        //try {
        //    $item->addMediaFromBase64(base64_encode($qrcode))
        //        ->usingFileName("qr_{$item->reference}.svg")
        //        ->toMediaCollection('barcode');
        //} catch (FileDoesNotExist|InvalidBase64Data|FileCannotBeAdded|FileIsTooBig $e) {
        //    Log::warning($e->getMessage());
        //}
    }
}
