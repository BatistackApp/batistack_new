<?php

namespace App\Filament\Gpao\Pages;

use App\Enums\Gpao\ManufacturingStatus;
use App\Filament\Gpao\ManufacturingOrders\ManufacturingOrderResource;
use App\Models\Gpao\ManufacturingOrder;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProductionCalendar extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Planning Capacitaire';

    protected static ?string $title = 'Calendrier de Production';

    protected static string|null|\UnitEnum $navigationGroup = 'Production';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.gpao.pages.production-calendar';

    public function getEventsProperty()
    {
        $orders = ManufacturingOrder::whereNotNull('start_date')
            ->whereIn('status', [
                ManufacturingStatus::PLANNED,
                ManufacturingStatus::IN_PROGRESS,
                ManufacturingStatus::QUALITY_CONTROL,
                ManufacturingStatus::COMPLETED,
            ])
            ->get();

        $events = [];

        foreach ($orders as $order) {
            $color = match ($order->status) {
                ManufacturingStatus::PLANNED => '#3b82f6', // blue
                ManufacturingStatus::IN_PROGRESS => '#f59e0b', // amber
                ManufacturingStatus::QUALITY_CONTROL => '#8b5cf6', // purple
                ManufacturingStatus::COMPLETED => '#10b981', // green
                default => '#6b7280', // gray
            };

            // Fullcalendar attend start et end (format YYYY-MM-DD)
            // Si end_date est null, on l'affiche sur 1 jour (start_date)
            $events[] = [
                'id' => $order->id,
                'title' => $order->reference.' - '.($order->item->name ?? ''),
                'start' => $order->start_date->format('Y-m-d'),
                'end' => $order->end_date ? $order->end_date->copy()->addDay()->format('Y-m-d') : $order->start_date->copy()->addDay()->format('Y-m-d'),
                'color' => $color,
                'url' => ManufacturingOrderResource::getUrl('edit', ['record' => $order->id]),
            ];
        }

        return $events;
    }

    public function updateEventDates($eventId, $startStr, $endStr)
    {
        $order = ManufacturingOrder::find($eventId);
        if ($order) {
            $order->update([
                'start_date' => $startStr ? Carbon::parse($startStr) : null,
                'end_date' => $endStr ? Carbon::parse($endStr)->subDay() : null, // FullCalendar end date is exclusive
            ]);

            Notification::make()
                ->title('Planning mis à jour')
                ->success()
                ->send();
        }
    }
}
