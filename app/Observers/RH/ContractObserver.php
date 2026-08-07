<?php

namespace App\Observers\RH;

use App\Models\RH\Contract;
use App\Services\RH\RHDocumentService;
use Log;

class ContractObserver
{
    public function __construct(public RHDocumentService $documentService)
    {
    }

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
        if ($contract->isDirty('job_title') || $contract->isDirty('start_date') || $contract->isDirty('end_date')) {
            if ($contract->isDirty('job_title') && $contract->getOriginal('job_title')) {
                if ($contract->employee?->user && \Spatie\Permission\Models\Role::where('name', $contract->getOriginal('job_title'))->exists()) {
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
        if ($contract->employee?->user && \Spatie\Permission\Models\Role::where('name', $contract->job_title)->exists()) {
            $contract->employee->user->removeRole($contract->job_title);
        }
        
        if ($contract->employee?->user) {
            $activeContract = $contract->employee->contracts()->active()->latest()->first();
            if ($activeContract && \Spatie\Permission\Models\Role::where('name', $activeContract->job_title)->exists()) {
                $contract->employee->user->assignRole($activeContract->job_title);
            }
        }
    }

    protected function syncUserRole(Contract $contract): void
    {
        $employee = $contract->employee;
        if ($employee && $employee->user) {
            $user = $employee->user;
            if ($contract->isActive()) {
                if (\Spatie\Permission\Models\Role::where('name', $contract->job_title)->exists()) {
                    $user->assignRole($contract->job_title);
                }
            } else {
                if (\Spatie\Permission\Models\Role::where('name', $contract->job_title)->exists()) {
                    $user->removeRole($contract->job_title);
                }
            }
        }
    }
}
