<?php

namespace App\Filament\Salarie\Pages;

use App\Enums\Gpao\ManufacturingStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\RH\TimeEntry;
use App\Services\Gpao\GpaoDocumentService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use ToneGabes\Filament\Icons\Enums\Phosphor;

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

    public function hasActiveTracking($orderId)
    {
        $employee = auth()->user()->salarie;
        if (! $employee) {
            return false;
        }

        return TimeEntry::where('employee_id', $employee->id)
            ->where('manufacturing_order_id', $orderId)
            ->where('status', TimeEntryStatus::DRAFT)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->exists();
    }

    public function startTracking($orderId)
    {
        $order = ManufacturingOrder::findOrFail($orderId);
        $employee = auth()->user()->salarie;

        if (! $employee) {
            Notification::make()->title('Erreur')->body('Vous n\'êtes pas lié à une fiche Salarié.')->danger()->send();

            return;
        }

        if ($order->status === ManufacturingStatus::PLANNED) {
            $order->update(['status' => ManufacturingStatus::IN_PROGRESS]);
        }

        // Démarrer une nouvelle session de pointage
        TimeEntry::create([
            'employee_id' => $employee->id,
            'manufacturing_order_id' => $order->id,
            'type' => TimeEntryType::NORMAL,
            'status' => TimeEntryStatus::DRAFT,
            'date' => now()->toDateString(),
            'started_at' => now(),
            'hours' => 0,
        ]);

        Notification::make()->title('Pointage démarré')->success()->send();
    }

    public function stopTracking($orderId)
    {
        $employee = auth()->user()->salarie;
        if (! $employee) {
            return;
        }

        $activeEntry = TimeEntry::where('employee_id', $employee->id)
            ->where('manufacturing_order_id', $orderId)
            ->where('status', TimeEntryStatus::DRAFT)
            ->whereNotNull('started_at')
            ->whereNull('ended_at')
            ->first();

        if ($activeEntry) {
            $endedAt = now();
            $durationInMinutes = $activeEntry->started_at->diffInMinutes($endedAt);
            $hours = round($durationInMinutes / 60, 2);

            $activeEntry->update([
                'ended_at' => $endedAt,
                'hours' => $hours,
                'status' => TimeEntryStatus::SUBMITTED, // Prêt à être approuvé par RH
            ]);

            Notification::make()->title('Pointage arrêté')->body("Temps enregistré : {$hours} h")->success()->send();
        }
    }

    public function finishOrder($orderId)
    {
        $order = ManufacturingOrder::findOrFail($orderId);
        if ($order->status === ManufacturingStatus::IN_PROGRESS) {
            // Arrêter le pointage en cours s'il y en a un
            $this->stopTracking($orderId);

            $order->update(['status' => ManufacturingStatus::QUALITY_CONTROL]);
            Notification::make()->title('OF Terminé')->body('Transféré au contrôle qualité.')->success()->send();
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
        $pdfPath = (new GpaoDocumentService)->generateManufacturingOrderPdf($order);
        $media = $order->addMedia($pdfPath)->toMediaCollection('pdf_documents');

        return response()->download($media->getPath(), $media->file_name);
    }
}
