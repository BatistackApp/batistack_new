<?php

namespace App\Services\Tiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthenticatedThirdPartyService
{
    /**
     * Resolve the unique company assigned to an authenticated third-party account.
     *
     * The account/company mapping is authoritative. Contacts only establish
     * that the account still has at least one active contact for that company.
     */
    public function resolve(User $user, ThirdPartyType $expectedType): ?ThirdParty
    {
        if (! $user->is_tiers) {
            return null;
        }

        $thirdPartyId = DB::table('user_third_party_accounts')
            ->where('user_id', $user->getKey())
            ->value('third_party_id');

        if ($thirdPartyId === null) {
            return null;
        }

        return ThirdParty::query()
            ->whereKey($thirdPartyId)
            ->where('type', $expectedType->value)
            ->where('is_active', true)
            ->whereHas('contacts', fn ($query) => $query
                ->where('user_id', $user->getKey())
                ->where('is_active', true))
            ->first();
    }

    public function customer(User $user): ?ThirdParty
    {
        return $this->resolve($user, ThirdPartyType::CLIENT);
    }

    public function subcontractor(User $user): ?ThirdParty
    {
        return $this->resolve($user, ThirdPartyType::SUBCONTRACTOR);
    }
}
