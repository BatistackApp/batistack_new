<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use App\Models\Commerce\CustomerSituation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CustomerSituationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerSituation');
    }

    public function view(AuthUser $authUser, CustomerSituation $customerSituation): bool
    {
        return $authUser->can('View:CustomerSituation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerSituation');
    }

    public function update(AuthUser $authUser, CustomerSituation $customerSituation): bool
    {
        return $authUser->can('Update:CustomerSituation');
    }

    public function delete(AuthUser $authUser, CustomerSituation $customerSituation): bool
    {
        return $authUser->can('Delete:CustomerSituation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomerSituation');
    }

    public function restore(AuthUser $authUser, CustomerSituation $customerSituation): bool
    {
        return $authUser->can('Restore:CustomerSituation');
    }

    public function forceDelete(AuthUser $authUser, CustomerSituation $customerSituation): bool
    {
        return $authUser->can('ForceDelete:CustomerSituation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomerSituation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomerSituation');
    }

    public function replicate(AuthUser $authUser, CustomerSituation $customerSituation): bool
    {
        return $authUser->can('Replicate:CustomerSituation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomerSituation');
    }
}
