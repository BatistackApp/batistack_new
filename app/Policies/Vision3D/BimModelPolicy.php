<?php

declare(strict_types=1);

namespace App\Policies\Vision3D;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Vision3D\BimModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class BimModelPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BimModel');
    }

    public function view(AuthUser $authUser, BimModel $bimModel): bool
    {
        return $authUser->can('View:BimModel');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BimModel');
    }

    public function update(AuthUser $authUser, BimModel $bimModel): bool
    {
        return $authUser->can('Update:BimModel');
    }

    public function delete(AuthUser $authUser, BimModel $bimModel): bool
    {
        return $authUser->can('Delete:BimModel');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BimModel');
    }

    public function restore(AuthUser $authUser, BimModel $bimModel): bool
    {
        return $authUser->can('Restore:BimModel');
    }

    public function forceDelete(AuthUser $authUser, BimModel $bimModel): bool
    {
        return $authUser->can('ForceDelete:BimModel');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BimModel');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BimModel');
    }

    public function replicate(AuthUser $authUser, BimModel $bimModel): bool
    {
        return $authUser->can('Replicate:BimModel');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BimModel');
    }

}