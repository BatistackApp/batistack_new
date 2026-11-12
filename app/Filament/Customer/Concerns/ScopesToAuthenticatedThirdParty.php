<?php

namespace App\\Filament\\Customer\\Concerns;

use App\\Enums\\Tiers\\ThirdPartyType;
use App\\Models\\Tiers\\ThirdParty;
use App\\Services\\Tiers\\AuthenticatedThirdPartyService;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Support\\Facades\\Schema;

trait ScopesToAuthenticatedThirdParty
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $thirdParty = static::authenticatedThirdParty();

        if (! $thirdParty) {
            return $query->whereRaw('1 = 0');
        }

        $model = new (static::getModel());
        $table = $model->getTable();
        $thirdPartyId = (int) $thirdParty->getKey();

        if (Schema::hasColumn($table, 'third_party_id')) {
            return $query->where($table.'.third_party_id', $thirdPartyId);
        }

        if (Schema::hasColumn($table, 'client_id')) {
            return $query->where($table.'.client_id', $thirdPartyId);
        }

        if (method_exists($model, 'order')) {
            return $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('client_id', $thirdPartyId));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canView(Model $record): bool
    {
        return static::recordBelongsToAuthenticatedThirdParty($record);
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    protected static function authenticatedThirdParty(): ?ThirdParty
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return app(AuthenticatedThirdPartyService::class)->resolve($user, ThirdPartyType::CLIENT);
    }

    protected static function recordBelongsToAuthenticatedThirdParty(Model $record): bool
    {
        $thirdParty = static::authenticatedThirdParty();

        if (! $thirdParty) {
            return false;
        }

        $thirdPartyId = (int) $thirdParty->getKey();

        if (array_key_exists('third_party_id', $record->getAttributes())) {
            return (int) $record->getAttribute('third_party_id') === $thirdPartyId;
        }

        if (array_key_exists('client_id', $record->getAttributes())) {
            return (int) $record->getAttribute('client_id') === $thirdPartyId;
        }

        if (method_exists($record, 'order')) {
            return (int) ($record->order?->client_id ?? 0) === $thirdPartyId;
        }

        return false;
    }
}
