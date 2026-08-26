<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use App\Models\Commerce\SupplierInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SupplierInvoicePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SupplierInvoice');
    }

    public function view(AuthUser $authUser, SupplierInvoice $supplierInvoice): bool
    {
        return $authUser->can('View:SupplierInvoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SupplierInvoice');
    }

    public function update(AuthUser $authUser, SupplierInvoice $supplierInvoice): bool
    {
        return $authUser->can('Update:SupplierInvoice');
    }

    public function delete(AuthUser $authUser, SupplierInvoice $supplierInvoice): bool
    {
        return $authUser->can('Delete:SupplierInvoice');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SupplierInvoice');
    }

    public function restore(AuthUser $authUser, SupplierInvoice $supplierInvoice): bool
    {
        return $authUser->can('Restore:SupplierInvoice');
    }

    public function forceDelete(AuthUser $authUser, SupplierInvoice $supplierInvoice): bool
    {
        return $authUser->can('ForceDelete:SupplierInvoice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SupplierInvoice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SupplierInvoice');
    }

    public function replicate(AuthUser $authUser, SupplierInvoice $supplierInvoice): bool
    {
        return $authUser->can('Replicate:SupplierInvoice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SupplierInvoice');
    }
}
