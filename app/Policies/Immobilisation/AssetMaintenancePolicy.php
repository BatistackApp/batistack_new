<?php

declare(strict_types=1);

namespace App\Policies\Immobilisation;

use App\Models\Immobilisation\AssetMaintenance;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AssetMaintenancePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AssetMaintenance');
    }

    public function view(AuthUser $authUser, AssetMaintenance $assetMaintenance): bool
    {
        return $authUser->can('View:AssetMaintenance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AssetMaintenance');
    }

    public function update(AuthUser $authUser, AssetMaintenance $assetMaintenance): bool
    {
        return $authUser->can('Update:AssetMaintenance');
    }

    public function delete(AuthUser $authUser, AssetMaintenance $assetMaintenance): bool
    {
        return $authUser->can('Delete:AssetMaintenance');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AssetMaintenance');
    }

    public function restore(AuthUser $authUser, AssetMaintenance $assetMaintenance): bool
    {
        return $authUser->can('Restore:AssetMaintenance');
    }

    public function forceDelete(AuthUser $authUser, AssetMaintenance $assetMaintenance): bool
    {
        return $authUser->can('ForceDelete:AssetMaintenance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AssetMaintenance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AssetMaintenance');
    }

    public function replicate(AuthUser $authUser, AssetMaintenance $assetMaintenance): bool
    {
        return $authUser->can('Replicate:AssetMaintenance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AssetMaintenance');
    }
}
