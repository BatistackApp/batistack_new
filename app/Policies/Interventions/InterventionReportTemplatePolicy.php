<?php

declare(strict_types=1);

namespace App\Policies\Interventions;

use App\Models\Interventions\InterventionReportTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class InterventionReportTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InterventionReportTemplate');
    }

    public function view(AuthUser $authUser, InterventionReportTemplate $template): bool
    {
        return $authUser->can('View:InterventionReportTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InterventionReportTemplate');
    }

    public function update(AuthUser $authUser, InterventionReportTemplate $template): bool
    {
        return $authUser->can('Update:InterventionReportTemplate');
    }

    public function delete(AuthUser $authUser, InterventionReportTemplate $template): bool
    {
        return $authUser->can('Delete:InterventionReportTemplate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:InterventionReportTemplate');
    }

    public function restore(AuthUser $authUser, InterventionReportTemplate $template): bool
    {
        return $authUser->can('Restore:InterventionReportTemplate');
    }

    public function forceDelete(AuthUser $authUser, InterventionReportTemplate $template): bool
    {
        return $authUser->can('ForceDelete:InterventionReportTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InterventionReportTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InterventionReportTemplate');
    }

    public function replicate(AuthUser $authUser, InterventionReportTemplate $template): bool
    {
        return $authUser->can('Replicate:InterventionReportTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InterventionReportTemplate');
    }
}