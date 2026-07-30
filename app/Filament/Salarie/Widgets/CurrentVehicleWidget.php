<?php

namespace App\Filament\Salarie\Widgets;

use Filament\Widgets\Widget;

class CurrentVehicleWidget extends Widget
{
    protected string $view = 'filament.salarie.widgets.current-vehicle-widget';
    protected int | string | array $columnSpan = 'full';

    public function getVehicle()
    {
        $employee = auth()->user()->salarie;
        if (!$employee) {
            return null;
        }

        // Find the active assignment for this employee
        $assignment = \App\Models\Flottes\VehicleAssignment::where('employee_id', $employee->id)
            ->where('status', \App\Enums\Flottes\AssignmentStatus::ACTIVE)
            ->with('vehicle')
            ->first();

        return $assignment ? $assignment->vehicle : null;
    }
}
