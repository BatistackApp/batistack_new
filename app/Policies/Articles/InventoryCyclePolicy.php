<?php

declare(strict_types=1);

namespace App\Policies\Articles;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Articles\InventoryCycle;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryCyclePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InventoryCycle');
    }

    public function view(AuthUser $authUser, InventoryCycle $inventoryCycle): bool
    {
        return $authUser->can('View:InventoryCycle');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InventoryCycle');
    }

    public function update(AuthUser $authUser, InventoryCycle $inventoryCycle): bool
    {
        return $authUser->can('Update:InventoryCycle');
    }

    public function delete(AuthUser $authUser, InventoryCycle $inventoryCycle): bool
    {
        return $authUser->can('Delete:InventoryCycle');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:InventoryCycle');
    }

    public function restore(AuthUser $authUser, InventoryCycle $inventoryCycle): bool
    {
        return $authUser->can('Restore:InventoryCycle');
    }

    public function forceDelete(AuthUser $authUser, InventoryCycle $inventoryCycle): bool
    {
        return $authUser->can('ForceDelete:InventoryCycle');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InventoryCycle');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InventoryCycle');
    }

    public function replicate(AuthUser $authUser, InventoryCycle $inventoryCycle): bool
    {
        return $authUser->can('Replicate:InventoryCycle');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InventoryCycle');
    }

}