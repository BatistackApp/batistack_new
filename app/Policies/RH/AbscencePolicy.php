<?php

declare(strict_types=1);

namespace App\Policies\RH;

use App\Models\RH\Abscence;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AbscencePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Abscence');
    }

    public function view(AuthUser $authUser, Abscence $abscence): bool
    {
        return $authUser->can('View:Abscence');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Abscence');
    }

    public function update(AuthUser $authUser, Abscence $abscence): bool
    {
        return $authUser->can('Update:Abscence');
    }

    public function delete(AuthUser $authUser, Abscence $abscence): bool
    {
        return $authUser->can('Delete:Abscence');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Abscence');
    }

    public function restore(AuthUser $authUser, Abscence $abscence): bool
    {
        return $authUser->can('Restore:Abscence');
    }

    public function forceDelete(AuthUser $authUser, Abscence $abscence): bool
    {
        return $authUser->can('ForceDelete:Abscence');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Abscence');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Abscence');
    }

    public function replicate(AuthUser $authUser, Abscence $abscence): bool
    {
        return $authUser->can('Replicate:Abscence');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Abscence');
    }
}
