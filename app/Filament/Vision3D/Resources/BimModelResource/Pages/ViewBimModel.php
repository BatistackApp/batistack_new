<?php

namespace App\Filament\Vision3D\Resources\BimModelResource\Pages;

use App\Filament\Commerce\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Vision3D\Resources\BimModelResource;
use App\Services\Articles\BomProcurementService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBimModel extends ViewRecord
{
    protected static string $resource = BimModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('generate_purchase_orders')
                ->label('Générer le bon de commande')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->modalHeading('Générer le bon de commande')
                ->modalDescription('Vérifiez les quantités à commander (besoin net après déduction du stock) avant de confirmer.')
                ->modalContent(fn () => view('filament.pages.bim-procurement-recap', [
                    'requirements' => app(BomProcurementService::class)->resolveRequirements($this->record),
                ]))
                ->action(function (BomProcurementService $service) {
                    abort_unless($this->canGeneratePurchaseOrders(), 403);

                    $result = $service->generatePurchaseOrders($this->record);
                    $purchaseOrders = $result['purchase_orders'];
                    $ignoredItems = $result['ignored_items'];

                    if (empty($purchaseOrders)) {
                        $body = empty($ignoredItems)
                            ? 'Le stock actuel couvre les quantitatifs de la maquette.'
                            : 'Le stock couvre les besoins, mais '.count($ignoredItems).' article(s) ont été ignorés car sans fournisseur.';

                        Notification::make()
                            ->title('Aucun bon de commande généré')
                            ->body($body)
                            ->warning()
                            ->send();

                        return;
                    }

                    if (! empty($ignoredItems)) {
                        Notification::make()
                            ->title(count($ignoredItems).' article(s) ignoré(s)')
                            ->body('Ces articles sont en rupture mais sans fournisseur renseigné.')
                            ->warning()
                            ->send();
                    }

                    $first = $purchaseOrders[0];

                    Notification::make()
                        ->title(count($purchaseOrders).' bon(s) de commande généré(s)')
                        ->success()
                        ->send();

                    $this->redirect(PurchaseOrderResource::getUrl('edit', ['record' => $first], panel: 'commerce'));
                })
                ->hidden(fn () => $this->record->quantities()->count() === 0
                    || ! $this->canGeneratePurchaseOrders()),
        ];
    }

    protected function canGeneratePurchaseOrders(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->can('Create:PurchaseOrder')
            && $user->can('Update:PurchaseOrder');
    }
}
