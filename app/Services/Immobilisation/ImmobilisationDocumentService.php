<?php

namespace App\Services\Immobilisation;

use App\Models\Chantiers\Chantier;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Immobilisation\AssetCategory;
use App\Services\Core\DocumentService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use App\Models\Immobilisation\AssetTransfer;

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
            ],
            filename: 'bon_transport_' . $transfer->id,
            type: 'immobilisations',
            position: 'portrait'
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
            'version'      => 5,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 4,
            'imageBase64'  => true,
        ]);

        // On encode l'URL pour la fiche via le Resource
        $qrCodeData = \App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\FixedAssetResource::getUrl('view', ['record' => $asset], panel: 'immobilisation');
        $qrCodeSvg = (new QRCode($options))->render($qrCodeData);

        return $this->generate(
            view: 'documents.immobilisations.asset_sheet',
            data: [
                'asset' => $asset,
                'qrCode' => $qrCodeSvg,
            ],
            filename: 'fiche_immobilisation_' . $asset->id,
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
                \App\Enums\Immobilisation\AssetStatus::ACTIVE,
            ]);
        }, 'fixedAssets.depreciations'])->get();

        return $this->generate(
            view: 'documents.immobilisations.global_schedule',
            data: [
                'categories' => $categories,
                'year' => $year,
            ],
            filename: 'etat_dotations_' . $year,
            type: 'immobilisations',
            position: 'landscape'
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
            ],
            filename: 'pv_cession_' . $asset->id,
            type: 'immobilisations',
            position: 'portrait'
        );
    }

    /**
     * Génère la fiche de récolement pour un chantier.
     */
    public function generateInventoryChecklist(Chantier $chantier): string
    {
        $assets = FixedAsset::where('chantier_id', $chantier->id)
            ->whereIn('status', [
                \App\Enums\Immobilisation\AssetStatus::ACTIVE,
            ])
            ->with('category')
            ->get();

        return $this->generate(
            view: 'documents.immobilisations.inventory_checklist',
            data: [
                'chantier' => $chantier,
                'assets' => $assets,
                'position' => 'portrait'
            ],
            filename: 'fiche_inventaire_chantier_' . $chantier->id,
            type: 'immobilisations'
        );
    }

    /**
     * Génère une plaquette PDF contenant les QR Codes des actifs donnés.
     */
    public function generateQrCodeSheet(\Illuminate\Support\Collection $assets): string
    {
        $options = new QROptions([
            'version'      => 5,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 3,
            'imageBase64'  => true,
        ]);

        $qrCodes = [];
        foreach ($assets as $asset) {
            $url = \App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\FixedAssetResource::getUrl('view', ['record' => $asset], panel: 'immobilisation');
            $qrCodes[$asset->id] = (new QRCode($options))->render($url);
        }

        return $this->generate(
            view: 'documents.immobilisations.qr_codes_sheet',
            data: [
                'assets' => $assets,
                'qrCodes' => $qrCodes,
                'position' => 'portrait'
            ],
            filename: 'plaquette_qr_codes_' . now()->format('Ymd_His'),
            type: 'immobilisations'
        );
    }
}
