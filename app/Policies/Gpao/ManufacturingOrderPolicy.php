<?php

declare(strict_types=1);

namespace App\Policies\Gpao;

use App\Models\Gpao\ManufacturingOrder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ManufacturingOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ManufacturingOrder');
    }

    public function view(AuthUser $authUser, ManufacturingOrder $manufacturingOrder): bool
    {
        return $authUser->can('View:ManufacturingOrder');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ManufacturingOrder');
    }

    public function update(AuthUser $authUser, ManufacturingOrder $manufacturingOrder): bool
    {
        return $authUser->can('Update:ManufacturingOrder');
    }

    public function delete(AuthUser $authUser, ManufacturingOrder $manufacturingOrder): bool
    {
        return $authUser->can('Delete:ManufacturingOrder');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ManufacturingOrder');
    }

    public function restore(AuthUser $authUser, ManufacturingOrder $manufacturingOrder): bool
    {
        return $authUser->can('Restore:ManufacturingOrder');
    }

    public function forceDelete(AuthUser $authUser, ManufacturingOrder $manufacturingOrder): bool
    {
        return $authUser->can('ForceDelete:ManufacturingOrder');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ManufacturingOrder');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ManufacturingOrder');
    }

    public function replicate(AuthUser $authUser, ManufacturingOrder $manufacturingOrder): bool
    {
        return $authUser->can('Replicate:ManufacturingOrder');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ManufacturingOrder');
    }
}
