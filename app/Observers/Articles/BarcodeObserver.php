<?php

namespace App\Observers\Articles;

use App\Models\Articles\Item;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\InvalidBase64Data;

class BarcodeObserver
{
    /**
     * Valider avant création
     */
    public function creating(Item $item): void
    {
        if ($item->isDirty('reference') && ! empty($item->reference)) {
            $this->validateBarcodeUnique($item->reference, null);
        }
    }

    /**
     * Valider avant mise à jour
     */
    public function updating(Item $item): void
    {
        if ($item->isDirty('reference')) {
            $this->validateBarcodeUnique($item->reference, $item->id);
        }
    }

    /**
     * Génère un QR Code lors de la création d'un article
     */
    public function created(Item $item): void
    {
        try {
            $this->generateQRCode($item);
            Log::info('QR Code généré', [
                'item_id' => $item->id,
                'reference' => $item->reference,
            ]);
        } catch (Exception $e) {
            Log::error('Erreur génération QR Code', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Régénère le QR Code si la référence change
     */
    public function updated(Item $item): void
    {
        if ($item->isDirty('reference')) {
            try {
                // Supprimer l'ancien barcode
                $item->clearMediaCollection('barcode');

                // Générer le nouveau
                $this->generateQRCode($item);

                Log::info('QR Code régénéré', [
                    'item_id' => $item->id,
                    'old_reference' => $item->getOriginal('reference'),
                    'new_reference' => $item->reference,
                ]);
            } catch (Exception $e) {
                Log::error('Erreur régénération QR Code', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Supprime les barcodes à la suppression
     */
    public function deleted(Item $item): void
    {
        try {
            $item->clearMediaCollection('barcode');
            Log::info('Barcode supprimé', [
                'item_id' => $item->id,
                'reference' => $item->reference,
            ]);
        } catch (Exception $e) {
            Log::warning('Erreur suppression barcode', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Générer le QR Code
     */
    private function generateQRCode(Item $item): void
    {
        // Vérifier que la collection est enregistrée
        if (! in_array('barcode', (array)$item->getRegisteredMediaCollections())) {
            $item->addMediaCollection('barcode')->singleFile();
        }

        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
        ]);

        // Le QR Code contient la référence de l'article
        $data = $item->reference;
        $qrcode = (new QRCode($options))->render($data);

        try {
            // Stocker le SVG dans la collection media
            $item->addMediaFromString($qrcode)
                ->usingFileName("qr_{$item->reference}.svg")
                ->withCustomProperties(['type' => 'qrcode'])
                ->toMediaCollection('barcode');
        } catch (FileDoesNotExist|InvalidBase64Data|FileCannotBeAdded|FileIsTooBig $e) {
            Log::warning('Impossible de stocker le QR Code', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Valider l'unicité du barcode
     * @throws Exception
     */
    private function validateBarcodeUnique(string $reference, ?int $excludeId = null): void
    {
        $query = Item::where('reference', $reference);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new Exception("La référence '{$reference}' est déjà utilisée");
        }
    }
}
