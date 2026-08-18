<?php

namespace App\Policies\Paie;

use App\Models\Paie\SalaryPaymentRun;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SalaryPaymentRunPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SalaryPaymentRun');
    }

    public function view(AuthUser $authUser, SalaryPaymentRun $run): bool
    {
        return $authUser->can('View:SalaryPaymentRun');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SalaryPaymentRun');
    }

    public function update(AuthUser $authUser, SalaryPaymentRun $run): bool
    {
        return $authUser->can('Update:SalaryPaymentRun');
    }

    public function delete(AuthUser $authUser, SalaryPaymentRun $run): bool
    {
        return $authUser->can('Delete:SalaryPaymentRun');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SalaryPaymentRun');
    }

    public function restore(AuthUser $authUser, SalaryPaymentRun $run): bool
    {
        return $authUser->can('Restore:SalaryPaymentRun');
    }

    public function forceDelete(AuthUser $authUser, SalaryPaymentRun $run): bool
    {
        return $authUser->can('ForceDelete:SalaryPaymentRun');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SalaryPaymentRun');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SalaryPaymentRun');
    }

    public function replicate(AuthUser $authUser, SalaryPaymentRun $run): bool
    {
        return $authUser->can('Replicate:SalaryPaymentRun');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SalaryPaymentRun');
    }
}
