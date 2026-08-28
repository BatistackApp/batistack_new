<?php

declare(strict_types=1);

namespace App\Policies\Interventions;

use App\Models\Interventions\ClientEquipment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ClientEquipmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ClientEquipment');
    }

    public function view(AuthUser $authUser, ClientEquipment $clientEquipment): bool
    {
        return $authUser->can('View:ClientEquipment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ClientEquipment');
    }

    public function update(AuthUser $authUser, ClientEquipment $clientEquipment): bool
    {
        return $authUser->can('Update:ClientEquipment');
    }

    public function delete(AuthUser $authUser, ClientEquipment $clientEquipment): bool
    {
        return $authUser->can('Delete:ClientEquipment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ClientEquipment');
    }

    public function restore(AuthUser $authUser, ClientEquipment $clientEquipment): bool
    {
        return $authUser->can('Restore:ClientEquipment');
    }

    public function forceDelete(AuthUser $authUser, ClientEquipment $clientEquipment): bool
    {
        return $authUser->can('ForceDelete:ClientEquipment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ClientEquipment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ClientEquipment');
    }

    public function replicate(AuthUser $authUser, ClientEquipment $clientEquipment): bool
    {
        return $authUser->can('Replicate:ClientEquipment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ClientEquipment');
    }
}
