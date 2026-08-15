<?php

declare(strict_types=1);

namespace App\Policies\Immobilisation;

use App\Models\Immobilisation\AssetMaintenanceTicket;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AssetMaintenanceTicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AssetMaintenanceTicket');
    }

    public function view(AuthUser $authUser, AssetMaintenanceTicket $ticket): bool
    {
        return $authUser->can('View:AssetMaintenanceTicket');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AssetMaintenanceTicket');
    }

    public function update(AuthUser $authUser, AssetMaintenanceTicket $ticket): bool
    {
        return $authUser->can('Update:AssetMaintenanceTicket');
    }

    public function delete(AuthUser $authUser, AssetMaintenanceTicket $ticket): bool
    {
        return $authUser->can('Delete:AssetMaintenanceTicket');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AssetMaintenanceTicket');
    }

    public function restore(AuthUser $authUser, AssetMaintenanceTicket $ticket): bool
    {
        return $authUser->can('Restore:AssetMaintenanceTicket');
    }

    public function forceDelete(AuthUser $authUser, AssetMaintenanceTicket $ticket): bool
    {
        return $authUser->can('ForceDelete:AssetMaintenanceTicket');
    }
}
