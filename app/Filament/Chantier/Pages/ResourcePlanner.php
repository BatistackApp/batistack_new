<?php

namespace App\Filament\Chantier\Pages;

use App\Models\Chantiers\ChantierTask;
use App\Models\Chantiers\ResourceAllocation;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class ResourcePlanner extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Chantiers';

    protected static ?string $title = 'Planning des Ressources';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.chantier.pages.resource-planner';

    public $currentWeekStart;

    public function mount()
    {
        $this->currentWeekStart = now()->startOfWeek()->format('Y-m-d');
    }

    public function nextWeek()
    {
        $this->currentWeekStart = Carbon::parse($this->currentWeekStart)->addWeek()->format('Y-m-d');
    }

    public function previousWeek()
    {
        $this->currentWeekStart = Carbon::parse($this->currentWeekStart)->subWeek()->format('Y-m-d');
    }

    #[Computed]
    public function getTasksProperty()
    {
        // Only load tasks that are active or not completed
        return ChantierTask::with('phase.chantier')->where('is_completed', false)->get();
    }

    #[Computed]
    public function getEmployeesProperty()
    {
        return Employee::where('is_active', true)->get();
    }

    #[Computed]
    public function getVehiclesProperty()
    {
        return Vehicle::all();
    }

    #[Computed]
    public function getAllocationsProperty()
    {
        $start = Carbon::parse($this->currentWeekStart);
        $end = $start->copy()->endOfWeek();

        return ResourceAllocation::with(['task', 'allocatable'])
            ->whereBetween('date', [$start, $end])
            ->get();
    }

    public function allocateResource($taskId, $type, $resourceId, $date)
    {
        $parsedDate = Carbon::parse($date);
        $allocatableType = $type === 'employee' ? Employee::class : Vehicle::class;

        // Check for conflicts: is the resource already allocated on this date?
        $existing = ResourceAllocation::where('allocatable_type', $allocatableType)
            ->where('allocatable_id', $resourceId)
            ->whereDate('date', $parsedDate)
            ->first();

        if ($existing) {
            Notification::make()
                ->title('Conflit de ressource')
                ->body('Cette ressource est déjà affectée à une autre tâche pour cette journée.')
                ->danger()
                ->send();

            return;
        }

        ResourceAllocation::create([
            'chantier_task_id' => $taskId,
            'allocatable_type' => $allocatableType,
            'allocatable_id' => $resourceId,
            'date' => $parsedDate->format('Y-m-d'),
        ]);

        Notification::make()
            ->title('Ressource affectée')
            ->success()
            ->send();
    }

    public function removeAllocation($allocationId)
    {
        ResourceAllocation::find($allocationId)?->delete();

        Notification::make()
            ->title('Affectation supprimée')
            ->success()
            ->send();
    }
}
