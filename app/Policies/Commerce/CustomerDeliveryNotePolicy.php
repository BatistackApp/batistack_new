<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use App\Models\Commerce\CustomerDeliveryNote;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CustomerDeliveryNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerDeliveryNote');
    }

    public function view(AuthUser $authUser, CustomerDeliveryNote $customerDeliveryNote): bool
    {
        return $authUser->can('View:CustomerDeliveryNote');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerDeliveryNote');
    }

    public function update(AuthUser $authUser, CustomerDeliveryNote $customerDeliveryNote): bool
    {
        return $authUser->can('Update:CustomerDeliveryNote');
    }

    public function delete(AuthUser $authUser, CustomerDeliveryNote $customerDeliveryNote): bool
    {
        return $authUser->can('Delete:CustomerDeliveryNote');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomerDeliveryNote');
    }

    public function restore(AuthUser $authUser, CustomerDeliveryNote $customerDeliveryNote): bool
    {
        return $authUser->can('Restore:CustomerDeliveryNote');
    }

    public function forceDelete(AuthUser $authUser, CustomerDeliveryNote $customerDeliveryNote): bool
    {
        return $authUser->can('ForceDelete:CustomerDeliveryNote');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomerDeliveryNote');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomerDeliveryNote');
    }

    public function replicate(AuthUser $authUser, CustomerDeliveryNote $customerDeliveryNote): bool
    {
        return $authUser->can('Replicate:CustomerDeliveryNote');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomerDeliveryNote');
    }
}
