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

    public function updateTaskDates(string $taskId, string $startStr, string $endStr)
    {
        $start = \Carbon\Carbon::parse($startStr);
        $end = \Carbon\Carbon::parse($endStr);

        if (str_starts_with($taskId, 'task_')) {
            $id = str_replace('task_', '', $taskId);
            $task = \App\Models\Chantiers\ChantierTask::find($id);
            if ($task) {
                $task->update([
                    'start_date' => $start,
                    'end_date' => $end,
                ]);
            }
        } elseif (str_starts_with($taskId, 'phase_')) {
            $id = str_replace('phase_', '', $taskId);
            $phase = \App\Models\Chantiers\ChantierPhase::find($id);
            if ($phase) {
                // Calculate difference in days to shift child tasks
                $originalStart = $phase->start_date ? clone $phase->start_date : $start;
                $diffInDays = $originalStart->diffInDays($start, false);

                $phase->update([
                    'start_date' => $start,
                    'end_date' => $end,
                ]);

                // Shift child tasks automatically
                if ($diffInDays !== 0) {
                    foreach ($phase->tasks as $childTask) {
                        if ($childTask->start_date) {
                            $childTask->start_date = $childTask->start_date->addDays($diffInDays);
                        }
                        if ($childTask->end_date) {
                            $childTask->end_date = $childTask->end_date->addDays($diffInDays);
                        }
                        $childTask->save();
                    }
                }
            }
        } elseif (str_starts_with($taskId, 'chantier_')) {
            $id = str_replace('chantier_', '', $taskId);
            $chantier = \App\Models\Chantiers\Chantier::find($id);
            if ($chantier) {
                $chantier->update([
                    'start_date_preview' => $start,
                    'end_date_preview' => $end,
                ]);
            }
        }
        
        \Filament\Notifications\Notification::make()
            ->title('Dates mises à jour')
            ->success()
            ->send();
    }

    public function updateTaskProgress(string $taskId, int $progress)
    {
        if (str_starts_with($taskId, 'task_')) {
            $id = str_replace('task_', '', $taskId);
            $task = \App\Models\Chantiers\ChantierTask::find($id);
            if ($task) {
                $task->update([
                    'progress_percentage' => $progress,
                    'is_completed' => $progress == 100,
                ]);
                
                \Filament\Notifications\Notification::make()
                    ->title('Avancement mis à jour')
                    ->success()
                    ->send();
            }
        }
    }
}
