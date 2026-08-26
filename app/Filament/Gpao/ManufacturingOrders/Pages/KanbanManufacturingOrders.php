<?php

namespace App\Filament\Gpao\ManufacturingOrders\Pages;

use App\Enums\Gpao\ManufacturingStatus;
use App\Filament\Gpao\ManufacturingOrders\ManufacturingOrderResource;
use App\Models\Gpao\ManufacturingOrder;
use App\Services\Gpao\ApsSchedulingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class KanbanManufacturingOrders extends Page
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected string $view = 'filament.resources.gpao.manufacturing-orders.pages.kanban-manufacturing-orders';

    protected static ?string $title = 'Kanban de Production';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-view-columns';

    public function getStatuses(): array
    {
        return ManufacturingStatus::cases();
    }

    public function getOrdersByStatus(string $status): Collection
    {
        return ManufacturingOrder::with('item')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateOrderStatus($orderId, $newStatus)
    {
        $order = ManufacturingOrder::find($orderId);
        if ($order && in_array($newStatus, array_column(ManufacturingStatus::cases(), 'value'))) {
            $order->update(['status' => $newStatus]);

            Notification::make()
                ->title('Statut mis à jour')
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ordonnancer')
                ->label('Ordonnancer (IA)')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->action(function (ApsSchedulingService $apsService) {
                    $apsService->scheduleOpenOrders();

                    Notification::make()
                        ->title('Ordonnancement terminé')
                        ->body('Les ordres de fabrication ont été réorganisés.')
                        ->success()
                        ->send();

                    // Refresh the livewire component
                    $this->dispatch('refresh-board');
                }),
            Action::make('list')
                ->label('Vue Liste')
                ->icon('heroicon-o-list-bullet')
                ->url(fn (): string => ManufacturingOrderResource::getUrl('index')),
        ];
    }
}
