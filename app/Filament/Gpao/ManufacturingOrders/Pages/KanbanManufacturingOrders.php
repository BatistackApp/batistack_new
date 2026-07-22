<?php

namespace App\Filament\Gpao\ManufacturingOrders\Pages;

use App\Filament\Gpao\ManufacturingOrders\ManufacturingOrderResource;
use Filament\Resources\Pages\Page;

class KanbanManufacturingOrders extends Page
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected string $view = 'filament.resources.gpao.manufacturing-orders.pages.kanban-manufacturing-orders';
    protected static ?string $title = 'Kanban de Production';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    public function getStatuses(): array
    {
        return \App\Enums\Gpao\ManufacturingStatus::cases();
    }

    public function getOrdersByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Gpao\ManufacturingOrder::with('item')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateOrderStatus($orderId, $newStatus)
    {
        $order = \App\Models\Gpao\ManufacturingOrder::find($orderId);
        if ($order && in_array($newStatus, array_column(\App\Enums\Gpao\ManufacturingStatus::cases(), 'value'))) {
            $order->update(['status' => $newStatus]);

            \Filament\Notifications\Notification::make()
                ->title('Statut mis à jour')
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('list')
                ->label('Vue Liste')
                ->icon('heroicon-o-list-bullet')
                ->url(fn (): string => ManufacturingOrderResource::getUrl('index')),
        ];
    }
}
