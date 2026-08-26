<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use App\Models\Commerce\SubcontractorSituation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SubcontractorSituationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SubcontractorSituation');
    }

    public function view(AuthUser $authUser, SubcontractorSituation $subcontractorSituation): bool
    {
        return $authUser->can('View:SubcontractorSituation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SubcontractorSituation');
    }

    public function update(AuthUser $authUser, SubcontractorSituation $subcontractorSituation): bool
    {
        return $authUser->can('Update:SubcontractorSituation');
    }

    public function delete(AuthUser $authUser, SubcontractorSituation $subcontractorSituation): bool
    {
        return $authUser->can('Delete:SubcontractorSituation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SubcontractorSituation');
    }

    public function restore(AuthUser $authUser, SubcontractorSituation $subcontractorSituation): bool
    {
        return $authUser->can('Restore:SubcontractorSituation');
    }

    public function forceDelete(AuthUser $authUser, SubcontractorSituation $subcontractorSituation): bool
    {
        return $authUser->can('ForceDelete:SubcontractorSituation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SubcontractorSituation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SubcontractorSituation');
    }

    public function replicate(AuthUser $authUser, SubcontractorSituation $subcontractorSituation): bool
    {
        return $authUser->can('Replicate:SubcontractorSituation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SubcontractorSituation');
    }
}
