<?php

declare(strict_types=1);

namespace App\Policies\Chantiers;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Chantiers\ChantierTask;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChantierTaskPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChantierTask');
    }

    public function view(AuthUser $authUser, ChantierTask $chantierTask): bool
    {
        return $authUser->can('View:ChantierTask');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChantierTask');
    }

    public function update(AuthUser $authUser, ChantierTask $chantierTask): bool
    {
        return $authUser->can('Update:ChantierTask');
    }

    public function delete(AuthUser $authUser, ChantierTask $chantierTask): bool
    {
        return $authUser->can('Delete:ChantierTask');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChantierTask');
    }

    public function restore(AuthUser $authUser, ChantierTask $chantierTask): bool
    {
        return $authUser->can('Restore:ChantierTask');
    }

    public function forceDelete(AuthUser $authUser, ChantierTask $chantierTask): bool
    {
        return $authUser->can('ForceDelete:ChantierTask');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChantierTask');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChantierTask');
    }

    public function replicate(AuthUser $authUser, ChantierTask $chantierTask): bool
    {
        return $authUser->can('Replicate:ChantierTask');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChantierTask');
    }

}