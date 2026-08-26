<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use App\Models\Commerce\SupplierCreditNote;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SupplierCreditNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SupplierCreditNote');
    }

    public function view(AuthUser $authUser, SupplierCreditNote $supplierCreditNote): bool
    {
        return $authUser->can('View:SupplierCreditNote');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SupplierCreditNote');
    }

    public function update(AuthUser $authUser, SupplierCreditNote $supplierCreditNote): bool
    {
        return $authUser->can('Update:SupplierCreditNote');
    }

    public function delete(AuthUser $authUser, SupplierCreditNote $supplierCreditNote): bool
    {
        return $authUser->can('Delete:SupplierCreditNote');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SupplierCreditNote');
    }

    public function restore(AuthUser $authUser, SupplierCreditNote $supplierCreditNote): bool
    {
        return $authUser->can('Restore:SupplierCreditNote');
    }

    public function forceDelete(AuthUser $authUser, SupplierCreditNote $supplierCreditNote): bool
    {
        return $authUser->can('ForceDelete:SupplierCreditNote');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SupplierCreditNote');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SupplierCreditNote');
    }

    public function replicate(AuthUser $authUser, SupplierCreditNote $supplierCreditNote): bool
    {
        return $authUser->can('Replicate:SupplierCreditNote');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SupplierCreditNote');
    }
}
