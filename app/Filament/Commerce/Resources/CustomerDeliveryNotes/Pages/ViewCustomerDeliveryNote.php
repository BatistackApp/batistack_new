<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Commerce\OrderStatus;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\Actions\WorkflowAction;
use App\Filament\Commerce\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use App\Models\Commerce\CustomerDeliveryNote;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Support\Facades\Log;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerDeliveryNote extends ViewRecord
{
    protected static string $resource = CustomerDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            WorkflowAction::make('shipping')
                ->action(function (CustomerDeliveryNote $record) {
                    // On décrémente les stocks des articles envoyer
                    // On compare les quantités envoyer avec les quantités commander afin de mettre le status de la commande affillier à PARTIELLY_DELIVERED ou DELIVERED
                    // On change le status de DELIVERYNOTE à Shipped, l'observers s'occupe d'envoyer la notification d'envoie au client
                    try {
                        $items = $record->items()->with(['item', 'orderItem', 'item.stocks'])->get();

                        foreach ($items as $item) {
                            $stockItem = $item->item->stocks()->first();
                            if ($stockItem) {
                                $stockItem->quantity -= $item->quantity_delivered;
                                $stockItem->save();
                            }
                        }
                    } catch (\Exception $exception) {
                        Log::error($exception->getMessage());

                        Notification::make()->danger()->title('Erreur lors de la décrémentation du stock')->send();
                    }

                    try {
                        $order = $record->order;

                        if ($order) {
                            $totalOrderedQuantity = 0;
                            $totalDeliveredQuantity = 0;

                            foreach ($order->items as $orderItem) {
                                $totalOrderedQuantity += $orderItem->quantity;
                                $deliveredForThisOrderItem = $orderItem->deliveryNoteItems()->sum('quantity_delivered');
                                $totalDeliveredQuantity += $deliveredForThisOrderItem;
                            }

                            if ($totalDeliveredQuantity >= $totalOrderedQuantity) {
                                $order->status = OrderStatus::DELIVERED;
                            } elseif ($totalDeliveredQuantity > 0) {
                                $order->status = OrderStatus::PARTIALLY_DELIVERED;
                            }
                            $order->save();
                        }

                        $record->status = DeliveryStatus::SHIPPED;
                        $record->save();

                        Notification::make()->success()->title('Commande envoyé au client')->send();
                    } catch (\Exception $exception) {
                        Log::error($exception->getMessage());

                        Notification::make()->danger()->title('Erreur lors de la mise à jour du status de la commande')->send();
                    }
                }),

            WorkflowAction::make('delivered')
                ->action(function (CustomerDeliveryNote $record) {
                    // On met juste à jour le status de DELIVERYNOTE à delivered
                    $record->status = DeliveryStatus::DELIVERED;
                    $record->delivery_date = now();
                    $record->save();

                    Notification::make()->success()->title('Commande réceptionnée')->send();
                }),

            MediaAction::make('pdf-view')
                ->tooltip('Voir la note de livraison en PDF')
                ->iconButton()
                ->icon(Phosphor::FilePdf)
                ->media(fn (CustomerDeliveryNote $record) => \Storage::disk('public')->url("documents/commerce/deliveries/bl_{$record->reference}.pdf")),
        ];
    }
}
