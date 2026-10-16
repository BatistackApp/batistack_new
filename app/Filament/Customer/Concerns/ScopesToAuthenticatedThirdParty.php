<?php

namespace App\Filament\Customer\Concerns;

use App\Models\Tiers\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait ScopesToAuthenticatedThirdParty
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $contact = static::authenticatedContact();

        if (! $contact) {
            return $query->whereRaw('1 = 0');
        }

        $model = new (static::getModel());
        $table = $model->getTable();
        $thirdPartyId = (int) $contact->third_party_id;

        if (Schema::hasColumn($table, 'third_party_id')) {
            return $query->where($table.'.third_party_id', $thirdPartyId);
        }

        if (Schema::hasColumn($table, 'client_id')) {
            return $query->where($table.'.client_id', $thirdPartyId);
        }

        // Customer situations belong to a customer through their order.
        if (method_exists($model, 'order')) {
            return $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('client_id', $thirdPartyId));
        }

        // Fail closed when a resource has no known customer ownership path.
        return $query->whereRaw('1 = 0');
    }

    public static function canView(Model $record): bool
    {
        return static::recordBelongsToAuthenticatedThirdParty($record);
    }

    public static function canEdit(Model $record): bool
    {
        // Client panel is read-only: no record may be edited.
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        // Client panel is read-only: no record may be deleted.
        return false;
    }

    protected static function authenticatedContact(): ?Contact
    {
        if (! auth()->check()) {
            return null;
        }

        $contact = Contact::query()
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->first();

        if (! $contact || ! $contact->third_party_id) {
            return null;
        }

        return $contact;
    }

    protected static function recordBelongsToAuthenticatedThirdParty(Model $record): bool
    {
        $contact = static::authenticatedContact();

        if (! $contact) {
            return false;
        }

        $thirdPartyId = (int) $contact->third_party_id;

        if (array_key_exists('third_party_id', $record->getAttributes())) {
            return (int) $record->getAttribute('third_party_id') === $thirdPartyId;
        }

        if (array_key_exists('client_id', $record->getAttributes())) {
            return (int) $record->getAttribute('client_id') === $thirdPartyId;
        }

        // Customer situations belong to a customer through their order.
        if (method_exists($record, 'order')) {
            return (int) ($record->order?->client_id ?? 0) === $thirdPartyId;
        }

        return false;
    }
}
