<?php

namespace App\Support;

use App\Models\Core\ReferenceCounter;
use Illuminate\Support\Facades\DB;

class ReferenceGenerator
{
    /**
     * Alloue la prochaine référence unique pour un préfixe donné (ex : TK, MC).
     * L'allocation est sérialisée via un compteur verrouillé en transaction.
     */
    public static function next(string $prefix): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($prefix, $year): string {
            $counter = ReferenceCounter::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    ['year' => $year, 'prefix' => $prefix],
                    ['last_number' => 0],
                );

            $counter->increment('last_number');

            return $prefix.'-'.$year.'-'.str_pad((string) $counter->last_number, 4, '0', STR_PAD_LEFT);
        });
    }
}
