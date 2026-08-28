<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use App\Models\Commerce\CustomerOrder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CustomerOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerOrder');
    }

    public function view(AuthUser $authUser, CustomerOrder $customerOrder): bool
    {
        return $authUser->can('View:CustomerOrder');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerOrder');
    }

    public function update(AuthUser $authUser, CustomerOrder $customerOrder): bool
    {
        return $authUser->can('Update:CustomerOrder');
    }

    public function delete(AuthUser $authUser, CustomerOrder $customerOrder): bool
    {
        return $authUser->can('Delete:CustomerOrder');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomerOrder');
    }

    public function restore(AuthUser $authUser, CustomerOrder $customerOrder): bool
    {
        return $authUser->can('Restore:CustomerOrder');
    }

    public function forceDelete(AuthUser $authUser, CustomerOrder $customerOrder): bool
    {
        return $authUser->can('ForceDelete:CustomerOrder');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomerOrder');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomerOrder');
    }

    public function replicate(AuthUser $authUser, CustomerOrder $customerOrder): bool
    {
        return $authUser->can('Replicate:CustomerOrder');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomerOrder');
    }
}
