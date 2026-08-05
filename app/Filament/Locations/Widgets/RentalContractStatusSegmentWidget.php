<?php

namespace App\Filament\Locations\Widgets;

use App\Models\Locations\RentalContract;
use App\Enums\Locations\RentalStatus;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\SegmentBarWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Segment;

class RentalContractStatusSegmentWidget extends SegmentBarWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Statut des Contrats de Location';
    }

    protected function getSegments(): array
    {
        $drafts = RentalContract::where('status', RentalStatus::DRAFT)->count();
        $actives = RentalContract::where('status', RentalStatus::ACTIVE)->count();
        $suspended = RentalContract::where('status', RentalStatus::SUSPENDED)->count();
        $terminated = RentalContract::where('status', RentalStatus::TERMINATED)->count();

        $segments = [];

        if ($drafts > 0) {
            $segments[] = Segment::make(RentalStatus::DRAFT->getLabel(), $drafts)->color(RentalStatus::DRAFT->getColor());
        }
        if ($actives > 0) {
            $segments[] = Segment::make(RentalStatus::ACTIVE->getLabel(), $actives)->color(RentalStatus::ACTIVE->getColor());
        }
        if ($suspended > 0) {
            $segments[] = Segment::make(RentalStatus::SUSPENDED->getLabel(), $suspended)->color(RentalStatus::SUSPENDED->getColor());
        }
        if ($terminated > 0) {
            $segments[] = Segment::make(RentalStatus::TERMINATED->getLabel(), $terminated)->color(RentalStatus::TERMINATED->getColor());
        }

        return $segments;
    }
}
