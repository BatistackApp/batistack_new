<?php

declare(strict_types=1);

namespace App\Policies\Chantiers;

use App\Models\Chantiers\Chantier;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ChantierPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Chantier');
    }

    public function view(AuthUser $authUser, Chantier $chantier): bool
    {
        return $authUser->can('View:Chantier');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Chantier');
    }

    public function update(AuthUser $authUser, Chantier $chantier): bool
    {
        return $authUser->can('Update:Chantier');
    }

    public function delete(AuthUser $authUser, Chantier $chantier): bool
    {
        return $authUser->can('Delete:Chantier');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Chantier');
    }

    public function restore(AuthUser $authUser, Chantier $chantier): bool
    {
        return $authUser->can('Restore:Chantier');
    }

    public function forceDelete(AuthUser $authUser, Chantier $chantier): bool
    {
        return $authUser->can('ForceDelete:Chantier');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Chantier');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Chantier');
    }

    public function replicate(AuthUser $authUser, Chantier $chantier): bool
    {
        return $authUser->can('Replicate:Chantier');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Chantier');
    }
}
