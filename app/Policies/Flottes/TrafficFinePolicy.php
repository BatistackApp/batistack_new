<?php

declare(strict_types=1);

namespace App\Policies\Flottes;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Flottes\TrafficFine;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrafficFinePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TrafficFine');
    }

    public function view(AuthUser $authUser, TrafficFine $trafficFine): bool
    {
        return $authUser->can('View:TrafficFine');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TrafficFine');
    }

    public function update(AuthUser $authUser, TrafficFine $trafficFine): bool
    {
        return $authUser->can('Update:TrafficFine');
    }

    public function delete(AuthUser $authUser, TrafficFine $trafficFine): bool
    {
        return $authUser->can('Delete:TrafficFine');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TrafficFine');
    }

    public function restore(AuthUser $authUser, TrafficFine $trafficFine): bool
    {
        return $authUser->can('Restore:TrafficFine');
    }

    public function forceDelete(AuthUser $authUser, TrafficFine $trafficFine): bool
    {
        return $authUser->can('ForceDelete:TrafficFine');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TrafficFine');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TrafficFine');
    }

    public function replicate(AuthUser $authUser, TrafficFine $trafficFine): bool
    {
        return $authUser->can('Replicate:TrafficFine');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TrafficFine');
    }

}