<?php

namespace App\Models\RH;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Chantiers\Chantier;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([\App\Observers\RH\ExpenseItemObserver::class])]
class ExpenseItem extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'expense_report_id',
        'chantier_id',
        'category',
        'date',
        'amount_ttc',
        'amount_ht',
        'vat_amount',
        'merchant',
        'status',
        'rejection_reason',
        'vehicle_id',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => \App\Enums\RH\ExpenseItemStatus::class,
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class, 'expense_report_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Flottes\Vehicle::class);
    }
}
