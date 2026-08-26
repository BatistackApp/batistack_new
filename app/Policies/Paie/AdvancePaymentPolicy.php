<?php

declare(strict_types=1);

namespace App\Policies\Paie;

use App\Models\Paie\AdvancePayment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AdvancePaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AdvancePayment');
    }

    public function view(AuthUser $authUser, AdvancePayment $advancePayment): bool
    {
        return $authUser->can('View:AdvancePayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AdvancePayment');
    }

    public function update(AuthUser $authUser, AdvancePayment $advancePayment): bool
    {
        return $authUser->can('Update:AdvancePayment');
    }

    public function delete(AuthUser $authUser, AdvancePayment $advancePayment): bool
    {
        return $authUser->can('Delete:AdvancePayment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AdvancePayment');
    }

    public function restore(AuthUser $authUser, AdvancePayment $advancePayment): bool
    {
        return $authUser->can('Restore:AdvancePayment');
    }

    public function forceDelete(AuthUser $authUser, AdvancePayment $advancePayment): bool
    {
        return $authUser->can('ForceDelete:AdvancePayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AdvancePayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AdvancePayment');
    }

    public function replicate(AuthUser $authUser, AdvancePayment $advancePayment): bool
    {
        return $authUser->can('Replicate:AdvancePayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AdvancePayment');
    }
}
