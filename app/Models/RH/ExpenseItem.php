<?php

namespace App\Models\RH;

use App\Enums\RH\ExpenseItemStatus;
use App\Enums\RH\ExpensePaymentMethod;
use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Observers\RH\ExpenseItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([ExpenseItemObserver::class])]
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
        'payment_method',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => ExpenseItemStatus::class,
        'payment_method' => ExpensePaymentMethod::class,
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
        return $this->belongsTo(Vehicle::class);
    }
}
