<?php

declare(strict_types=1);

namespace App\Policies\Locations;

use App\Models\Locations\RentalContract;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RentalContractPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RentalContract');
    }

    public function view(AuthUser $authUser, RentalContract $rentalContract): bool
    {
        return $authUser->can('View:RentalContract');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RentalContract');
    }

    public function update(AuthUser $authUser, RentalContract $rentalContract): bool
    {
        return $authUser->can('Update:RentalContract');
    }

    public function delete(AuthUser $authUser, RentalContract $rentalContract): bool
    {
        return $authUser->can('Delete:RentalContract');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RentalContract');
    }

    public function restore(AuthUser $authUser, RentalContract $rentalContract): bool
    {
        return $authUser->can('Restore:RentalContract');
    }

    public function forceDelete(AuthUser $authUser, RentalContract $rentalContract): bool
    {
        return $authUser->can('ForceDelete:RentalContract');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RentalContract');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RentalContract');
    }

    public function replicate(AuthUser $authUser, RentalContract $rentalContract): bool
    {
        return $authUser->can('Replicate:RentalContract');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RentalContract');
    }
}
