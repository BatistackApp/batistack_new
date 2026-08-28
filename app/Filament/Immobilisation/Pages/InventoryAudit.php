<?php

namespace App\Filament\Immobilisation\Pages;

use App\Models\Immobilisation\FixedAsset;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class InventoryAudit extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected string $view = 'filament.immobilisation.pages.inventory-audit';

    protected static ?string $navigationLabel = 'Audit d\'inventaire (Scan)';

    protected static ?string $title = 'Audit d\'inventaire';

    protected static string|null|\UnitEnum $navigationGroup = 'Gestion des Actifs';

    public $scannedUrl = '';

    public function processScan()
    {
        // Extract the ID from the Filament view URL
        // Example URL: http://gestion.c2me.ovh/immobilisation/fixed-assets/1
        if (preg_match('/fixed-assets\/(\d+)/', $this->scannedUrl, $matches)) {
            $assetId = $matches[1];
            $asset = FixedAsset::find($assetId);

            if ($asset) {
                $asset->update(['last_inventoried_at' => now()]);

                Notification::make()
                    ->title('Actif audité avec succès')
                    ->body("L'actif {$asset->name} a été marqué comme présent.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Actif introuvable')
                    ->danger()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('URL invalide')
                ->body('Le QR Code scanné ne correspond pas à un actif valide.')
                ->danger()
                ->send();
        }

        $this->scannedUrl = ''; // Reset input
    }
}
