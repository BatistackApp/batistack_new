<?php

declare(strict_types=1);

namespace App\Policies\Flottes;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Flottes\VehicleAssignment;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehicleAssignmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VehicleAssignment');
    }

    public function view(AuthUser $authUser, VehicleAssignment $vehicleAssignment): bool
    {
        return $authUser->can('View:VehicleAssignment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VehicleAssignment');
    }

    public function update(AuthUser $authUser, VehicleAssignment $vehicleAssignment): bool
    {
        return $authUser->can('Update:VehicleAssignment');
    }

    public function delete(AuthUser $authUser, VehicleAssignment $vehicleAssignment): bool
    {
        return $authUser->can('Delete:VehicleAssignment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:VehicleAssignment');
    }

    public function restore(AuthUser $authUser, VehicleAssignment $vehicleAssignment): bool
    {
        return $authUser->can('Restore:VehicleAssignment');
    }

    public function forceDelete(AuthUser $authUser, VehicleAssignment $vehicleAssignment): bool
    {
        return $authUser->can('ForceDelete:VehicleAssignment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VehicleAssignment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VehicleAssignment');
    }

    public function replicate(AuthUser $authUser, VehicleAssignment $vehicleAssignment): bool
    {
        return $authUser->can('Replicate:VehicleAssignment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VehicleAssignment');
    }

}