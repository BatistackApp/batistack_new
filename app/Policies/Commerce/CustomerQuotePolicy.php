<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Commerce\CustomerQuote;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerQuotePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CustomerQuote');
    }

    public function view(AuthUser $authUser, CustomerQuote $customerQuote): bool
    {
        return $authUser->can('View:CustomerQuote');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CustomerQuote');
    }

    public function update(AuthUser $authUser, CustomerQuote $customerQuote): bool
    {
        return $authUser->can('Update:CustomerQuote');
    }

    public function delete(AuthUser $authUser, CustomerQuote $customerQuote): bool
    {
        return $authUser->can('Delete:CustomerQuote');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CustomerQuote');
    }

    public function restore(AuthUser $authUser, CustomerQuote $customerQuote): bool
    {
        return $authUser->can('Restore:CustomerQuote');
    }

    public function forceDelete(AuthUser $authUser, CustomerQuote $customerQuote): bool
    {
        return $authUser->can('ForceDelete:CustomerQuote');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CustomerQuote');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CustomerQuote');
    }

    public function replicate(AuthUser $authUser, CustomerQuote $customerQuote): bool
    {
        return $authUser->can('Replicate:CustomerQuote');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CustomerQuote');
    }

}