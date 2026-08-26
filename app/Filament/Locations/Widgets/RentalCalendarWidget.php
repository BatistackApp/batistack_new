<?php

namespace App\Filament\Locations\Widgets;

use App\Filament\Locations\Resources\RentalContracts\RentalContractResource;
use App\Models\Locations\RentalContract;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RentalCalendarWidget extends CalendarWidget
{
    public function getEvents(FetchInfo $info): Collection|Builder|array
    {
        return RentalContract::query()
            ->with(['chantier', 'supplier'])
            ->get()
            ->map(function (RentalContract $contract) {

                $title = "{$contract->reference} - {$contract->chantier?->name}";
                if ($contract->supplier) {
                    $title .= " ({$contract->supplier->name})";
                }

                return CalendarEvent::make()
                    ->title($title)
                    ->start($contract->start_date)
                    ->end($contract->end_date ?? $contract->end_date_preview ?? $contract->start_date->copy()->addDays(30))
                    ->allDay(true)
                    ->backgroundColor(match ($contract->status?->value) {
                        'draft' => 'gray',
                        'active' => '#10b981', // green
                        'terminated' => '#f59e0b', // amber
                        default => 'primary',
                    });
                // Commented out to avoid click routing errors if Guava calendar expects a specific format
                // ->url(RentalContractResource::getUrl('view', ['record' => $contract->id]));
            })
            ->toArray();
    }
}
