<?php

declare(strict_types=1);

namespace App\Policies\Tiers;

use App\Models\Tiers\Consultation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ConsultationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Consultation');
    }

    public function view(AuthUser $authUser, Consultation $consultation): bool
    {
        return $authUser->can('View:Consultation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Consultation');
    }

    public function update(AuthUser $authUser, Consultation $consultation): bool
    {
        return $authUser->can('Update:Consultation');
    }

    public function delete(AuthUser $authUser, Consultation $consultation): bool
    {
        return $authUser->can('Delete:Consultation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Consultation');
    }

    public function restore(AuthUser $authUser, Consultation $consultation): bool
    {
        return $authUser->can('Restore:Consultation');
    }

    public function forceDelete(AuthUser $authUser, Consultation $consultation): bool
    {
        return $authUser->can('ForceDelete:Consultation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Consultation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Consultation');
    }

    public function replicate(AuthUser $authUser, Consultation $consultation): bool
    {
        return $authUser->can('Replicate:Consultation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Consultation');
    }
}
