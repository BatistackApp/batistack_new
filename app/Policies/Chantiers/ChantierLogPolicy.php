<?php

declare(strict_types=1);

namespace App\Policies\Chantiers;

use App\Models\Chantiers\ChantierLog;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ChantierLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChantierLog');
    }

    public function view(AuthUser $authUser, ChantierLog $chantierLog): bool
    {
        return $authUser->can('View:ChantierLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChantierLog');
    }

    public function update(AuthUser $authUser, ChantierLog $chantierLog): bool
    {
        return $authUser->can('Update:ChantierLog');
    }

    public function delete(AuthUser $authUser, ChantierLog $chantierLog): bool
    {
        return $authUser->can('Delete:ChantierLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChantierLog');
    }

    public function restore(AuthUser $authUser, ChantierLog $chantierLog): bool
    {
        return $authUser->can('Restore:ChantierLog');
    }

    public function forceDelete(AuthUser $authUser, ChantierLog $chantierLog): bool
    {
        return $authUser->can('ForceDelete:ChantierLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChantierLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChantierLog');
    }

    public function replicate(AuthUser $authUser, ChantierLog $chantierLog): bool
    {
        return $authUser->can('Replicate:ChantierLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChantierLog');
    }
}
