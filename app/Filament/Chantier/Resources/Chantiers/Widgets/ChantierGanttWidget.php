<?php

namespace App\Filament\Chantier\Resources\Chantiers\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class ChantierGanttWidget extends Widget
{
    protected string $view = 'filament.chantier.widgets.chantier-gantt-widget';

    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    public function getTasks(): array
    {
        if (!$this->record) {
            return [];
        }

        $chantier = $this->record;

        $tasks = [];

        // Add the Chantier as the parent milestone
        $chantierStart = $chantier->start_date_preview ? $chantier->start_date_preview->format('Y-m-d') : now()->format('Y-m-d');
        $chantierEnd = $chantier->end_date_preview ? $chantier->end_date_preview->format('Y-m-d') : now()->addMonths(1)->format('Y-m-d');

        $tasks[] = [
            'id' => 'chantier_' . $chantier->id,
            'name' => $chantier->name,
            'start' => $chantierStart,
            'end' => $chantierEnd,
            'progress' => 0, // Could be calculated
            'dependencies' => '',
            'custom_class' => 'bar-milestone',
        ];

        // Load phases and tasks
        $phases = $chantier->phases()->with('tasks')->get();

        foreach ($phases as $phase) {
            $phaseId = 'phase_' . $phase->id;

            $phaseStart = $phase->start_date ? $phase->start_date->format('Y-m-d') : $chantierStart;
            $phaseEnd = $phase->end_date ? $phase->end_date->format('Y-m-d') : $chantierEnd;

            $tasks[] = [
                'id' => $phaseId,
                'name' => 'Phase: ' . $phase->label,
                'start' => $phaseStart,
                'end' => $phaseEnd,
                'progress' => 0,
                'dependencies' => 'chantier_' . $chantier->id,
                'custom_class' => 'bar-phase',
            ];

            $previousTaskId = null;
            foreach ($phase->tasks as $task) {
                $taskId = 'task_' . $task->id;

                $taskStart = $task->start_date ? $task->start_date->format('Y-m-d') : $phaseStart;
                $taskEnd = $task->end_date ? $task->end_date->format('Y-m-d') : $phaseEnd;

                $dependencies = $phaseId;
                if ($previousTaskId) {
                    $dependencies .= ', ' . $previousTaskId;
                }

                $tasks[] = [
                    'id' => $taskId,
                    'name' => $task->label,
                    'start' => $taskStart,
                    'end' => $taskEnd,
                    'progress' => $task->progress_percentage ?? 0,
                    'dependencies' => $dependencies,
                    'custom_class' => $task->is_completed ? 'bar-task-completed' : 'bar-task',
                ];

                $previousTaskId = $taskId;
            }
        }

        return $tasks;
    }
}
