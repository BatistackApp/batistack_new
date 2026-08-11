<?php

namespace App\Filament\Customer\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesToAuthenticatedThirdParty
{
    public static function getEloquentQuery(): Builder
    {
        $contact = \App\Models\Tiers\Contact::where('user_id', auth()->id())->first();
        
        $query = parent::getEloquentQuery();
        
        if (! $contact) {
            return $query->whereRaw('1 = 0');
        }
        
        return $query->where('third_party_id', $contact->third_party_id);
    }
}
