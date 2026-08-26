<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use App\Models\Commerce\ReceiptNote;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReceiptNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReceiptNote');
    }

    public function view(AuthUser $authUser, ReceiptNote $receiptNote): bool
    {
        return $authUser->can('View:ReceiptNote');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReceiptNote');
    }

    public function update(AuthUser $authUser, ReceiptNote $receiptNote): bool
    {
        return $authUser->can('Update:ReceiptNote');
    }

    public function delete(AuthUser $authUser, ReceiptNote $receiptNote): bool
    {
        return $authUser->can('Delete:ReceiptNote');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReceiptNote');
    }

    public function restore(AuthUser $authUser, ReceiptNote $receiptNote): bool
    {
        return $authUser->can('Restore:ReceiptNote');
    }

    public function forceDelete(AuthUser $authUser, ReceiptNote $receiptNote): bool
    {
        return $authUser->can('ForceDelete:ReceiptNote');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReceiptNote');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReceiptNote');
    }

    public function replicate(AuthUser $authUser, ReceiptNote $receiptNote): bool
    {
        return $authUser->can('Replicate:ReceiptNote');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReceiptNote');
    }
}
