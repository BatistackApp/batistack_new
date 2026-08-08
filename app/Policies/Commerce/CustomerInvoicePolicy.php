<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Commerce\CustomerInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerInvoicePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerInvoice');
    }

    public function view(AuthUser $authUser, CustomerInvoice $customerInvoice): bool
    {
        return $authUser->can('View:CustomerInvoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerInvoice');
    }

    public function update(AuthUser $authUser, CustomerInvoice $customerInvoice): bool
    {
        return $authUser->can('Update:CustomerInvoice');
    }

    public function delete(AuthUser $authUser, CustomerInvoice $customerInvoice): bool
    {
        return $authUser->can('Delete:CustomerInvoice');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomerInvoice');
    }

    public function restore(AuthUser $authUser, CustomerInvoice $customerInvoice): bool
    {
        return $authUser->can('Restore:CustomerInvoice');
    }

    public function forceDelete(AuthUser $authUser, CustomerInvoice $customerInvoice): bool
    {
        return $authUser->can('ForceDelete:CustomerInvoice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomerInvoice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomerInvoice');
    }

    public function replicate(AuthUser $authUser, CustomerInvoice $customerInvoice): bool
    {
        return $authUser->can('Replicate:CustomerInvoice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomerInvoice');
    }

}