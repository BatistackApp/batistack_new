<?php

declare(strict_types=1);

namespace App\Policies\Chantiers;

use App\Models\Chantiers\ChecklistTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ChecklistTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ChecklistTemplate');
    }

    public function view(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('View:ChecklistTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ChecklistTemplate');
    }

    public function update(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('Update:ChecklistTemplate');
    }

    public function delete(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('Delete:ChecklistTemplate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ChecklistTemplate');
    }

    public function restore(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('Restore:ChecklistTemplate');
    }

    public function forceDelete(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('ForceDelete:ChecklistTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ChecklistTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ChecklistTemplate');
    }

    public function replicate(AuthUser $authUser, ChecklistTemplate $checklistTemplate): bool
    {
        return $authUser->can('Replicate:ChecklistTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ChecklistTemplate');
    }
}
