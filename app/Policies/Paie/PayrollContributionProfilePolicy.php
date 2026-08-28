<?php

declare(strict_types=1);

namespace App\Policies\Paie;

use App\Models\Paie\PayrollContributionProfile;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PayrollContributionProfilePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PayrollContributionProfile');
    }

    public function view(AuthUser $authUser, PayrollContributionProfile $payrollContributionProfile): bool
    {
        return $authUser->can('View:PayrollContributionProfile');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PayrollContributionProfile');
    }

    public function update(AuthUser $authUser, PayrollContributionProfile $payrollContributionProfile): bool
    {
        return $authUser->can('Update:PayrollContributionProfile');
    }

    public function delete(AuthUser $authUser, PayrollContributionProfile $payrollContributionProfile): bool
    {
        return $authUser->can('Delete:PayrollContributionProfile');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PayrollContributionProfile');
    }

    public function restore(AuthUser $authUser, PayrollContributionProfile $payrollContributionProfile): bool
    {
        return $authUser->can('Restore:PayrollContributionProfile');
    }

    public function forceDelete(AuthUser $authUser, PayrollContributionProfile $payrollContributionProfile): bool
    {
        return $authUser->can('ForceDelete:PayrollContributionProfile');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PayrollContributionProfile');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PayrollContributionProfile');
    }

    public function replicate(AuthUser $authUser, PayrollContributionProfile $payrollContributionProfile): bool
    {
        return $authUser->can('Replicate:PayrollContributionProfile');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PayrollContributionProfile');
    }
}
