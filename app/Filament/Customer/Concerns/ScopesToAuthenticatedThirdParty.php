<?php

namespace App\Filament\Customer\Concerns;

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
        $contact = \App\Models\Tiers\Contact::where('user_id', auth()->id())->first();

        if (! $contact) {
            return false;
        }

        $thirdPartyId = $record?->third_party_id ?? null;

        return $thirdPartyId !== null
            && (int) $thirdPartyId === (int) $contact->third_party_id;
    }
}
