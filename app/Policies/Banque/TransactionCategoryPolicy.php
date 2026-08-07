<?php

declare(strict_types=1);

namespace App\Policies\Banque;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Banque\TransactionCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TransactionCategory');
    }

    public function view(AuthUser $authUser, TransactionCategory $transactionCategory): bool
    {
        return $authUser->can('View:TransactionCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TransactionCategory');
    }

    public function update(AuthUser $authUser, TransactionCategory $transactionCategory): bool
    {
        return $authUser->can('Update:TransactionCategory');
    }

    public function delete(AuthUser $authUser, TransactionCategory $transactionCategory): bool
    {
        return $authUser->can('Delete:TransactionCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TransactionCategory');
    }

    public function restore(AuthUser $authUser, TransactionCategory $transactionCategory): bool
    {
        return $authUser->can('Restore:TransactionCategory');
    }

    public function forceDelete(AuthUser $authUser, TransactionCategory $transactionCategory): bool
    {
        return $authUser->can('ForceDelete:TransactionCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TransactionCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TransactionCategory');
    }

    public function replicate(AuthUser $authUser, TransactionCategory $transactionCategory): bool
    {
        return $authUser->can('Replicate:TransactionCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TransactionCategory');
    }

}