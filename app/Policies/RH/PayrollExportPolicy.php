<?php

declare(strict_types=1);

namespace App\Policies\RH;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RH\PayrollExport;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollExportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PayrollExport');
    }

    public function view(AuthUser $authUser, PayrollExport $payrollExport): bool
    {
        return $authUser->can('View:PayrollExport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PayrollExport');
    }

    public function update(AuthUser $authUser, PayrollExport $payrollExport): bool
    {
        return $authUser->can('Update:PayrollExport');
    }

    public function delete(AuthUser $authUser, PayrollExport $payrollExport): bool
    {
        return $authUser->can('Delete:PayrollExport');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PayrollExport');
    }

    public function restore(AuthUser $authUser, PayrollExport $payrollExport): bool
    {
        return $authUser->can('Restore:PayrollExport');
    }

    public function forceDelete(AuthUser $authUser, PayrollExport $payrollExport): bool
    {
        return $authUser->can('ForceDelete:PayrollExport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PayrollExport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PayrollExport');
    }

    public function replicate(AuthUser $authUser, PayrollExport $payrollExport): bool
    {
        return $authUser->can('Replicate:PayrollExport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PayrollExport');
    }

}