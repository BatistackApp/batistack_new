<?php

namespace App\Filament\Salarie\Pages;

use App\Models\Gpao\ManufacturingOrder;
use App\Enums\Gpao\ManufacturingStatus;
use Filament\Pages\Page;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class AtelierProduction extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Phosphor::Factory;

    protected string $view = 'filament.salarie.pages.atelier-production';

    protected static ?string $title = 'Atelier de Production';

    protected static ?string $navigationLabel = 'Atelier';

    protected static string|null|\UnitEnum $navigationGroup = 'Production';

    public $activeTab = 'todo';

    public static function canAccess(): bool
    {
        return auth()->user()?->access_atelier ?? false;
    }

    #[Computed]
    public function todoOrders()
    {
        return ManufacturingOrder::with(['item.media', 'requirements.item'])
            ->whereIn('status', [ManufacturingStatus::PLANNED, ManufacturingStatus::IN_PROGRESS])
            ->orderByRaw("FIELD(status, 'in_progress', 'planned')")
            ->orderBy('created_at', 'asc')
            ->get();
    }

    #[Computed]
    public function historyOrders()
    {
        return ManufacturingOrder::with(['item.media', 'requirements.item'])
            ->whereIn('status', [ManufacturingStatus::QUALITY_CONTROL, ManufacturingStatus::COMPLETED])
            ->orderBy('updated_at', 'desc')
            ->take(20)
            ->get();
    }

    public function startOrder($orderId)
    {
        $order = ManufacturingOrder::findOrFail($orderId);
        if ($order->status === ManufacturingStatus::PLANNED) {
            $order->update(['status' => ManufacturingStatus::IN_PROGRESS]);
        }
    }

    public function finishOrder($orderId)
    {
        $order = ManufacturingOrder::findOrFail($orderId);
        if ($order->status === ManufacturingStatus::IN_PROGRESS) {
            $order->update(['status' => ManufacturingStatus::QUALITY_CONTROL]);
        }
    }

    public function downloadPdf($orderId)
    {
        $order = ManufacturingOrder::findOrFail($orderId);
        
        $media = $order->getFirstMedia('pdf_documents');
        if ($media) {
            return response()->download($media->getPath(), $media->file_name);
        }

        // Generate on the fly
        $pdfPath = (new \App\Services\Gpao\GpaoDocumentService())->generateManufacturingOrderPdf($order);
        $media = $order->addMedia($pdfPath)->toMediaCollection('pdf_documents');
        
        return response()->download($media->getPath(), $media->file_name);
    }
}
