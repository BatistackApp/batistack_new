<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use App\Models\Commerce\PurchaseRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PurchaseRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PurchaseRequest');
    }

    public function view(AuthUser $authUser, PurchaseRequest $purchaseRequest): bool
    {
        return $authUser->can('View:PurchaseRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PurchaseRequest');
    }

    public function update(AuthUser $authUser, PurchaseRequest $purchaseRequest): bool
    {
        return $authUser->can('Update:PurchaseRequest');
    }

    public function delete(AuthUser $authUser, PurchaseRequest $purchaseRequest): bool
    {
        return $authUser->can('Delete:PurchaseRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PurchaseRequest');
    }

    public function restore(AuthUser $authUser, PurchaseRequest $purchaseRequest): bool
    {
        return $authUser->can('Restore:PurchaseRequest');
    }

    public function forceDelete(AuthUser $authUser, PurchaseRequest $purchaseRequest): bool
    {
        return $authUser->can('ForceDelete:PurchaseRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PurchaseRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PurchaseRequest');
    }

    public function replicate(AuthUser $authUser, PurchaseRequest $purchaseRequest): bool
    {
        return $authUser->can('Replicate:PurchaseRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PurchaseRequest');
    }
}
