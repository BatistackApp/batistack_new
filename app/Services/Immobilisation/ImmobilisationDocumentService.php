<?php

namespace App\Services\Immobilisation;

use App\Enums\Immobilisation\AssetStatus;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\FixedAssetResource;
use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\AssetCategory;
use App\Models\Immobilisation\AssetTransfer;
use App\Models\Immobilisation\FixedAsset;
use App\Models\RH\Equipement;
use App\Services\Core\DocumentService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImmobilisationDocumentService extends DocumentService
{
    /**
     * Génère le bon de transport pour le transfert d'un équipement.
     */
    public function generateTransferDocument(AssetTransfer $transfer): string
    {
        $transfer->load(['fixedAsset.category', 'fromChantier', 'toChantier', 'requester']);

        return $this->generate(
            view: 'documents.immobilisations.transfer_document',
            data: [
                'transfer' => $transfer,
                'position' => 'portrait',
            ],
            filename: 'bon_transport_'.$transfer->id,
            type: 'immobilisations',
        );
    }

    /**
     * Génère la fiche individuelle d'une immobilisation avec QR Code.
     */
    public function generateAssetSheet(FixedAsset $asset): string
    {
        $asset->load(['category', 'chantier', 'vehicle', 'depreciations']);

        // Options pour chillerlan/php-qrcode
        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 4,
            'imageBase64' => true,
        ]);

        // On encode l'URL pour la fiche via le Resource
        $qrCodeData = FixedAssetResource::getUrl('view', ['record' => $asset], panel: 'immobilisation');
        $qrCodeSvg = (new QRCode($options))->render($qrCodeData);

        return $this->generate(
            view: 'documents.immobilisations.asset_sheet',
            data: [
                'asset' => $asset,
                'qrCode' => $qrCodeSvg,
            ],
            filename: 'fiche_immobilisation_'.$asset->id,
            type: 'immobilisations',
        );
    }

    /**
     * Génère le tableau global des dotations prévisionnelles pour l'année donnée.
     */
    public function generateGlobalDepreciationSchedule(int $year): string
    {
        $categories = AssetCategory::with(['fixedAssets' => function ($query) {
            $query->whereIn('status', [
                AssetStatus::ACTIVE,
            ]);
        }, 'fixedAssets.depreciations'])->get();

        return $this->generate(
            view: 'documents.immobilisations.global_schedule',
            data: [
                'categories' => $categories,
                'year' => $year,
                'position' => 'landscape',
            ],
            filename: 'etat_dotations_'.$year,
            type: 'immobilisations',
        );
    }

    /**
     * Génère le PV de cession / mise au rebut.
     */
    public function generateDisposalCertificate(FixedAsset $asset): string
    {
        $asset->load('category');

        return $this->generate(
            view: 'documents.immobilisations.disposal_certificate',
            data: [
                'asset' => $asset,
                'position' => 'portrait',
            ],
            filename: 'pv_cession_'.$asset->id,
            type: 'immobilisations',
        );
    }

    /**
     * Génère la fiche de récolement pour un chantier.
     */
    public function generateInventoryChecklist(Chantier $chantier): string
    {
        $assets = FixedAsset::where('chantier_id', $chantier->id)
            ->whereIn('status', [
                AssetStatus::ACTIVE,
            ])
            ->with('category')
            ->get();

        return $this->generate(
            view: 'documents.immobilisations.inventory_checklist',
            data: [
                'chantier' => $chantier,
                'assets' => $assets,
                'position' => 'portrait',
            ],
            filename: 'fiche_inventaire_chantier_'.$chantier->id,
            type: 'immobilisations'
        );
    }

    /**
     * Génère une étiquette QR imprimable pour un actif (immobilisation ou équipement RH).
     */
    public function generateQrLabel(Model $asset): string
    {
        if (blank($asset->qr_token)) {
            $prefix = $asset instanceof FixedAsset ? 'FA-' : ($asset instanceof Equipement ? 'EQ-' : 'QR-');
            $asset->forceFill(['qr_token' => $prefix.strtoupper(Str::random(12))])->save();
        }

        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 6,
            'imageBase64' => true,
        ]);

        $qrCode = (new QRCode($options))->render($asset->qr_token);

        return $this->generate(
            view: 'documents.immobilisations.qr_label',
            data: [
                'asset' => $asset,
                'qrCode' => $qrCode,
            ],
            filename: 'etiquette_qr_'.class_basename($asset).'_'.$asset->getKey(),
            type: 'immobilisations'
        );
    }

    /**
     * Génère une plaquette PDF contenant les QR Codes des actifs donnés.
     */
    public function generateQrCodeSheet(Collection $assets): string
    {
        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 3,
            'imageBase64' => true,
        ]);

        $qrCodes = [];
        foreach ($assets as $asset) {
            $url = FixedAssetResource::getUrl('view', ['record' => $asset], panel: 'immobilisation');
            $qrCodes[$asset->id] = (new QRCode($options))->render($url);
        }

        return $this->generate(
            view: 'documents.immobilisations.qr_codes_sheet',
            data: [
                'assets' => $assets,
                'qrCodes' => $qrCodes,
                'position' => 'portrait',
            ],
            filename: 'plaquette_qr_codes_'.now()->format('Ymd_His'),
            type: 'immobilisations'
        );
    }
}
