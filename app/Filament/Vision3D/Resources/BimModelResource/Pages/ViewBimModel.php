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
                    $purchaseOrders = $service->generatePurchaseOrders($this->record);

                    if (empty($purchaseOrders)) {
                        Notification::make()
                            ->title('Aucun besoin à commander')
                            ->body('Le stock actuel couvre les quantitatifs de la maquette.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $first = $purchaseOrders[0];

                    Notification::make()
                        ->title(count($purchaseOrders).' bon(s) de commande généré(s)')
                        ->success()
                        ->send();

                    $this->redirect(PurchaseOrderResource::getUrl('edit', ['record' => $first], panel: 'commerce'));
                })
                ->hidden(fn () => $this->record->quantities()->count() === 0),
        ];
    }
}
