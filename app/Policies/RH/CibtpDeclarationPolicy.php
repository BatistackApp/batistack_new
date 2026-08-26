<?php

declare(strict_types=1);

namespace App\Policies\RH;

use App\Models\RH\CibtpDeclaration;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CibtpDeclarationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CibtpDeclaration');
    }

    public function view(AuthUser $authUser, CibtpDeclaration $cibtpDeclaration): bool
    {
        return $authUser->can('View:CibtpDeclaration');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CibtpDeclaration');
    }

    public function update(AuthUser $authUser, CibtpDeclaration $cibtpDeclaration): bool
    {
        return $authUser->can('Update:CibtpDeclaration');
    }

    public function delete(AuthUser $authUser, CibtpDeclaration $cibtpDeclaration): bool
    {
        return $authUser->can('Delete:CibtpDeclaration');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CibtpDeclaration');
    }

    public function restore(AuthUser $authUser, CibtpDeclaration $cibtpDeclaration): bool
    {
        return $authUser->can('Restore:CibtpDeclaration');
    }

    public function forceDelete(AuthUser $authUser, CibtpDeclaration $cibtpDeclaration): bool
    {
        return $authUser->can('ForceDelete:CibtpDeclaration');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CibtpDeclaration');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CibtpDeclaration');
    }

    public function replicate(AuthUser $authUser, CibtpDeclaration $cibtpDeclaration): bool
    {
        return $authUser->can('Replicate:CibtpDeclaration');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CibtpDeclaration');
    }
}
