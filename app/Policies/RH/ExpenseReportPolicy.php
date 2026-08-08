<?php

declare(strict_types=1);

namespace App\Policies\RH;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RH\ExpenseReport;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpenseReportPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ExpenseReport');
    }

    public function view(AuthUser $authUser, ExpenseReport $expenseReport): bool
    {
        return $authUser->can('View:ExpenseReport');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ExpenseReport');
    }

    public function update(AuthUser $authUser, ExpenseReport $expenseReport): bool
    {
        return $authUser->can('Update:ExpenseReport');
    }

    public function delete(AuthUser $authUser, ExpenseReport $expenseReport): bool
    {
        return $authUser->can('Delete:ExpenseReport');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ExpenseReport');
    }

    public function restore(AuthUser $authUser, ExpenseReport $expenseReport): bool
    {
        return $authUser->can('Restore:ExpenseReport');
    }

    public function forceDelete(AuthUser $authUser, ExpenseReport $expenseReport): bool
    {
        return $authUser->can('ForceDelete:ExpenseReport');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ExpenseReport');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ExpenseReport');
    }

    public function replicate(AuthUser $authUser, ExpenseReport $expenseReport): bool
    {
        return $authUser->can('Replicate:ExpenseReport');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ExpenseReport');
    }

}