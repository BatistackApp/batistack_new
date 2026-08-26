<?php

namespace App\Filament\Customer\Concerns;

use App\Models\Tiers\Contact;
use Illuminate\Database\Eloquent\Builder;

trait ScopesToAuthenticatedThirdParty
{
    public static function canView($record): bool
    {
        return static::recordBelongsToAuthenticatedThirdParty($record);
    }

    public static function canEdit($record): bool
    {
        // Client panel is read-only: no record may be edited.
        return false;
    }

    public static function canDelete($record): bool
    {
        // Client panel is read-only: no record may be deleted.
        return false;
    }

    protected static function recordBelongsToAuthenticatedThirdParty($record): bool
    {
        $contact = Contact::where('user_id', auth()->id())->first();

        $query = parent::getEloquentQuery();

        if (! $contact) {
            return false;
        }

        return $query->where('third_party_id', $contact->third_party_id);
    }
}
