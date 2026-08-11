<?php

declare(strict_types=1);

namespace App\Policies\Gpao;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Gpao\Machine;
use Illuminate\Auth\Access\HandlesAuthorization;

class MachinePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Machine');
    }

    public function view(AuthUser $authUser, Machine $machine): bool
    {
        return $authUser->can('View:Machine');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Machine');
    }

    public function update(AuthUser $authUser, Machine $machine): bool
    {
        return $authUser->can('Update:Machine');
    }

    public function delete(AuthUser $authUser, Machine $machine): bool
    {
        return $authUser->can('Delete:Machine');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Machine');
    }

    public function restore(AuthUser $authUser, Machine $machine): bool
    {
        return $authUser->can('Restore:Machine');
    }

    public function forceDelete(AuthUser $authUser, Machine $machine): bool
    {
        return $authUser->can('ForceDelete:Machine');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Machine');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Machine');
    }

    public function replicate(AuthUser $authUser, Machine $machine): bool
    {
        return $authUser->can('Replicate:Machine');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Machine');
    }

}