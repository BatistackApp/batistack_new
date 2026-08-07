<?php

declare(strict_types=1);

namespace App\Policies\RH;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RH\Equipement;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipementPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Equipement');
    }

    public function view(AuthUser $authUser, Equipement $equipement): bool
    {
        return $authUser->can('View:Equipement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Equipement');
    }

    public function update(AuthUser $authUser, Equipement $equipement): bool
    {
        return $authUser->can('Update:Equipement');
    }

    public function delete(AuthUser $authUser, Equipement $equipement): bool
    {
        return $authUser->can('Delete:Equipement');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Equipement');
    }

    public function restore(AuthUser $authUser, Equipement $equipement): bool
    {
        return $authUser->can('Restore:Equipement');
    }

    public function forceDelete(AuthUser $authUser, Equipement $equipement): bool
    {
        return $authUser->can('ForceDelete:Equipement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Equipement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Equipement');
    }

    public function replicate(AuthUser $authUser, Equipement $equipement): bool
    {
        return $authUser->can('Replicate:Equipement');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Equipement');
    }

}