<?php

namespace App\Observers\Immobilisation;

use App\Models\Immobilisation\FixedAsset;
use App\Services\Immobilisation\DepreciationCalculatorService;
use App\Services\Locations\InternalRentalBillingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FixedAssetObserver
{
    /**
     * Handle the FixedAsset "creating" event.
     */
    public function creating(FixedAsset $fixedAsset): void
    {
        if (empty($fixedAsset->qr_token)) {
            $fixedAsset->qr_token = 'FA-'.strtoupper(Str::random(12));
        }
    }

    /**
     * Handle the FixedAsset "created" event.
     */
    public function created(FixedAsset $fixedAsset): void
    {
        $calculator = app(DepreciationCalculatorService::class);
        $schedule = $calculator->generateSchedule($fixedAsset);

        foreach ($schedule as $depreciation) {
            $fixedAsset->depreciations()->create($depreciation);
        }

        // Si l'immo est créée avec un chantier, on initialise sa trace d'affectation
        $this->trackAssignmentChange($fixedAsset);

        $this->billForAffectation($fixedAsset);
    }

    /**
     * Handle the FixedAsset "updated" event.
     */
    public function updated(FixedAsset $fixedAsset): void
    {
        if ($fixedAsset->wasChanged('chantier_id')) {
            $this->billForAffectation($fixedAsset);
            $this->trackAssignmentChange($fixedAsset);
        }

        if ($fixedAsset->wasChanged('useful_life_years')) {
            $this->regenerateSchedule($fixedAsset);
        }
    }

    /**
     * Maintient l'historique des affectations : ferme l'enregistrement en cours
     * et en ouvre un nouveau si l'immo est affectée à un chantier.
     */
    protected function trackAssignmentChange(FixedAsset $fixedAsset): void
    {
        $open = $fixedAsset->assignments()
            ->whereNull('released_at')
            ->latest('id')
            ->first();

        $releaseReason = $fixedAsset->release_reason;

        if ($open) {
            $open->update([
                'released_at' => now(),
                'reason' => $releaseReason ?? $open->reason,
            ]);
        }

        if ($fixedAsset->chantier_id) {
            $fixedAsset->assignments()->create([
                'chantier_id' => $fixedAsset->chantier_id,
                'assigned_at' => now(),
                'released_at' => null,
                'assigned_by' => Auth::id(),
                'reason' => null,
            ]);
        }

        $fixedAsset->release_reason = null;
    }

    /**
     * Conserve les dotations déjà passées (nécessaires à l'analytique des chantiers)
     * et régénère uniquement les échéances futures à partir de la durée modifiée.
     */
    protected function regenerateSchedule(FixedAsset $fixedAsset): void
    {
        $passed = $fixedAsset->depreciations()->where('is_passed', true)->get();

        $lastPassedYear = $passed->map(fn ($d) => $d->period_date?->year)->max();
        if ($lastPassedYear === null) {
            $lastPassedYear = Carbon::parse($fixedAsset->purchase_date)->year - 1;
        }

        // On ne supprime que les échéances futures (non passées)
        $fixedAsset->depreciations()->where('is_passed', false)->delete();

        $baseValue = (float) $fixedAsset->purchase_price - (float) $fixedAsset->salvage_value;
        $passedAmount = (float) $passed->sum('amount');
        $impairmentAmount = (float) $fixedAsset->impairments()->sum('amount');
        $remainingVnc = $baseValue - $passedAmount - $impairmentAmount;

        if ($remainingVnc <= 0) {
            return;
        }

        $calculator = app(DepreciationCalculatorService::class);
        $theoretical = $calculator->generateSchedule($fixedAsset);

        $future = array_filter(
            $theoretical,
            fn ($item) => Carbon::parse($item['period_date'])->year > $lastPassedYear
        );

        if (empty($future)) {
            return;
        }

        $sumFuture = (float) array_sum(array_column($future, 'amount'));
        if ($sumFuture <= 0) {
            return;
        }

        $ratio = $remainingVnc / $sumFuture;
        $runningVnc = $remainingVnc;
        $keys = array_keys($future);

        foreach ($future as $index => $period) {
            if ($index === end($keys)) {
                $amount = round($runningVnc, 2);
            } else {
                $amount = round((float) $period['amount'] * $ratio, 2);
            }

            $runningVnc -= $amount;

            $fixedAsset->depreciations()->create([
                'period_date' => $period['period_date'],
                'amount' => max(0, $amount),
                'remaining_vnc' => max(0, $runningVnc),
            ]);
        }
    }

    /**
     * Déclenche la facturation interne lors de l'affectation d'un actif à un chantier.
     */
    protected function billForAffectation(FixedAsset $fixedAsset): void
    {
        if (! $fixedAsset->chantier_id || ! $fixedAsset->daily_rate) {
            return;
        }

        app(InternalRentalBillingService::class)->generateForAsset($fixedAsset);
    }

    /**
     * Handle the FixedAsset "deleted" event.
     */
    public function deleted(FixedAsset $fixedAsset): void
    {
        //
    }

    /**
     * Handle the FixedAsset "restored" event.
     */
    public function restored(FixedAsset $fixedAsset): void
    {
        //
    }

    /**
     * Handle the FixedAsset "force deleted" event.
     */
    public function forceDeleted(FixedAsset $fixedAsset): void
    {
        //
    }
}
