<?php

namespace App\Observers\RH;

use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Services\RH\RHDocumentService;
use Log;
use Spatie\Permission\Models\Role;

class ContractObserver
{
    public function __construct(public RHDocumentService $documentService) {}

    /**
     * @throws \Exception
     */
    public function creating(Contract $contract): void
    {
        if (! $contract->employee_id) {
            throw new \Exception('Employee required');
        }
        if (! $contract->start_date) {
            throw new \Exception('Start date required');
        }
        if ($contract->hourly_rate < 0) {
            throw new \Exception('Amounts must be positive');
        }
    }

    public function created(Contract $contract): void
    {
        $this->documentService->generateContract($contract);
        $this->syncUserRole($contract);
        Log::info('Contract created', ['id' => $contract->id, 'type' => $contract->type->getLabel(), 'employee_id' => $contract->employee_id]);
    }

    public function updated(Contract $contract): void
    {
        if ($contract->isDirty('employee_id') && $contract->getOriginal('employee_id')) {
            $oldEmployee = Employee::find($contract->getOriginal('employee_id'));
            if ($oldEmployee && $oldEmployee->user) {
                $oldJobTitle = $contract->getOriginal('job_title');
                if ($oldJobTitle && Role::where('name', $oldJobTitle)->exists()) {
                    $oldEmployee->user->removeRole($oldJobTitle);
                }
                // Réconcilier le vieil employé
                if ($activeOld = $oldEmployee->contracts()->active()->latest()->first()) {
                    if (Role::where('name', $activeOld->job_title)->exists()) {
                        $oldEmployee->user->assignRole($activeOld->job_title);
                    }
                }
            }
        }

        if ($contract->isDirty('job_title') || $contract->isDirty('start_date') || $contract->isDirty('end_date') || $contract->isDirty('employee_id')) {
            if ($contract->isDirty('job_title') && $contract->getOriginal('job_title')) {
                if ($contract->employee?->user && Role::where('name', $contract->getOriginal('job_title'))->exists()) {
                    $contract->employee->user->removeRole($contract->getOriginal('job_title'));
                }
            }
            $this->syncUserRole($contract);
        }
    }

    /**
     * @throws \Exception
     */
    public function deleting(Contract $contract): void
    {
        if ($contract->isActive()) {
            throw new \Exception('Cannot delete active contract');
        }
    }

    public function deleted(Contract $contract): void
    {
        if ($contract->employee?->user && Role::where('name', $contract->job_title)->exists()) {
            $contract->employee->user->removeRole($contract->job_title);
        }

        if ($contract->employee?->user) {
            $activeContract = $contract->employee->contracts()->active()->latest()->first();
            if ($activeContract && Role::where('name', $activeContract->job_title)->exists()) {
                $contract->employee->user->assignRole($activeContract->job_title);
            }
        }
    }

    public function syncUserRole(Contract $contract): void
    {
        $employee = $contract->employee;
        if ($employee && $employee->user) {
            $user = $employee->user;

            $activeJobTitles = $employee->contracts()->active()->pluck('job_title')->filter()->unique()->toArray();

            if (! $contract->isActive() && $contract->job_title && ! in_array($contract->job_title, $activeJobTitles)) {
                if (Role::where('name', $contract->job_title)->exists()) {
                    $user->removeRole($contract->job_title);
                }
            }

            foreach ($activeJobTitles as $jobTitle) {
                if (Role::where('name', $jobTitle)->exists()) {
                    $user->assignRole($jobTitle);
                }
            }
        }
    }
}
