<?php

declare(strict_types=1);

namespace App\Policies\Interventions;

use App\Models\Interventions\MaintenanceContract;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MaintenanceContractPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MaintenanceContract');
    }

    public function view(AuthUser $authUser, MaintenanceContract $contract): bool
    {
        return $authUser->can('View:MaintenanceContract');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MaintenanceContract');
    }

    public function update(AuthUser $authUser, MaintenanceContract $contract): bool
    {
        return $authUser->can('Update:MaintenanceContract');
    }

    public function delete(AuthUser $authUser, MaintenanceContract $contract): bool
    {
        return $authUser->can('Delete:MaintenanceContract');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MaintenanceContract');
    }

    public function restore(AuthUser $authUser, MaintenanceContract $contract): bool
    {
        return $authUser->can('Restore:MaintenanceContract');
    }

    public function forceDelete(AuthUser $authUser, MaintenanceContract $contract): bool
    {
        return $authUser->can('ForceDelete:MaintenanceContract');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MaintenanceContract');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MaintenanceContract');
    }

    public function replicate(AuthUser $authUser, MaintenanceContract $contract): bool
    {
        return $authUser->can('Replicate:MaintenanceContract');
    }
}
