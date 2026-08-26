<?php

declare(strict_types=1);

namespace App\Policies\RH;

use App\Models\RH\ExpenseAdvance;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ExpenseAdvancePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ExpenseAdvance');
    }

    public function view(AuthUser $authUser, ExpenseAdvance $expenseAdvance): bool
    {
        return $authUser->can('View:ExpenseAdvance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ExpenseAdvance');
    }

    public function update(AuthUser $authUser, ExpenseAdvance $expenseAdvance): bool
    {
        return $authUser->can('Update:ExpenseAdvance');
    }

    public function delete(AuthUser $authUser, ExpenseAdvance $expenseAdvance): bool
    {
        return $authUser->can('Delete:ExpenseAdvance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ExpenseAdvance');
    }

    public function restore(AuthUser $authUser, ExpenseAdvance $expenseAdvance): bool
    {
        return $authUser->can('Restore:ExpenseAdvance');
    }

    public function forceDelete(AuthUser $authUser, ExpenseAdvance $expenseAdvance): bool
    {
        return $authUser->can('ForceDelete:ExpenseAdvance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ExpenseAdvance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ExpenseAdvance');
    }

    public function replicate(AuthUser $authUser, ExpenseAdvance $expenseAdvance): bool
    {
        return $authUser->can('Replicate:ExpenseAdvance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ExpenseAdvance');
    }
}
