<?php

declare(strict_types=1);

namespace App\Policies\Tiers;

use App\Models\Tiers\ThirdParty;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ThirdPartyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ThirdParty');
    }

    public function view(AuthUser $authUser, ThirdParty $thirdParty): bool
    {
        return $authUser->can('View:ThirdParty');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ThirdParty');
    }

    public function update(AuthUser $authUser, ThirdParty $thirdParty): bool
    {
        return $authUser->can('Update:ThirdParty');
    }

    public function delete(AuthUser $authUser, ThirdParty $thirdParty): bool
    {
        return $authUser->can('Delete:ThirdParty');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ThirdParty');
    }

    public function restore(AuthUser $authUser, ThirdParty $thirdParty): bool
    {
        return $authUser->can('Restore:ThirdParty');
    }

    public function forceDelete(AuthUser $authUser, ThirdParty $thirdParty): bool
    {
        return $authUser->can('ForceDelete:ThirdParty');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ThirdParty');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ThirdParty');
    }

    public function replicate(AuthUser $authUser, ThirdParty $thirdParty): bool
    {
        return $authUser->can('Replicate:ThirdParty');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ThirdParty');
    }
}
