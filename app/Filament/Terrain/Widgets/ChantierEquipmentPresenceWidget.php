<?php

namespace App\Filament\Terrain\Widgets;

use App\Models\Chantiers\ChantierEquipmentTracking;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ChantierEquipmentPresenceWidget extends Widget
{
    protected static ?int $sort = 5;

    protected string $view = 'filament.terrain.widgets.equipment-presence';

    protected int|string|array $columnSpan = 'full';

    public ?int $chantierId = null;

    public array $presences = [];

    public float $totalCost = 0;

    public function mount(): void
    {
        $this->loadPresences();
    }

    public function setChantierId(?int $id): void
    {
        $this->chantierId = $id;
        $this->loadPresences();
    }

    protected function loadPresences(): void
    {
        $query = ChantierEquipmentTracking::query()
            ->with('trackable', 'chantier')
            ->whereNull('check_out_at')
            ->whereDate('check_in_at', today());

        if ($this->chantierId) {
            $query->where('chantier_id', $this->chantierId);
        }

        $trackings = $query->latest('check_in_at')->get();

        $this->presences = $trackings->map(fn ($t) => [
            'id' => $t->id,
            'label' => $t->getTrackableLabel(),
            'type' => $t->getTrackableTypeLabel(),
            'type_class' => str_contains($t->trackable_type, 'FixedAsset') ? 'orange' : 'blue',
            'chantier' => $t->chantier?->name ?? '',
            'since' => $t->check_in_at->format('H:i'),
            'duration' => $t->getDurationInDays(),
            'daily_rate' => $t->getDailyRate(),
            'cost' => $t->getImmobilizationCost(),
        ])->toArray();

        $this->totalCost = $trackings->sum(fn ($t) => $t->getImmobilizationCost());
    }
}
