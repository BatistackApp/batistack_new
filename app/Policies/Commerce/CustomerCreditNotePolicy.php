<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Commerce\CustomerCreditNote;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerCreditNotePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerCreditNote');
    }

    public function view(AuthUser $authUser, CustomerCreditNote $customerCreditNote): bool
    {
        return $authUser->can('View:CustomerCreditNote');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerCreditNote');
    }

    public function update(AuthUser $authUser, CustomerCreditNote $customerCreditNote): bool
    {
        return $authUser->can('Update:CustomerCreditNote');
    }

    public function delete(AuthUser $authUser, CustomerCreditNote $customerCreditNote): bool
    {
        return $authUser->can('Delete:CustomerCreditNote');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomerCreditNote');
    }

    public function restore(AuthUser $authUser, CustomerCreditNote $customerCreditNote): bool
    {
        return $authUser->can('Restore:CustomerCreditNote');
    }

    public function forceDelete(AuthUser $authUser, CustomerCreditNote $customerCreditNote): bool
    {
        return $authUser->can('ForceDelete:CustomerCreditNote');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomerCreditNote');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomerCreditNote');
    }

    public function replicate(AuthUser $authUser, CustomerCreditNote $customerCreditNote): bool
    {
        return $authUser->can('Replicate:CustomerCreditNote');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomerCreditNote');
    }

}