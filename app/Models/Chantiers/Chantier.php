<?php

namespace App\Models\Chantiers;

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Banque\BankTransaction;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\Facture;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Locations\InternalRentalInvoice;
use App\Models\Locations\RentalContract;
use App\Models\RH\CibtpDeclaration;
use App\Models\RH\Employee;
use App\Models\RH\ExpenseItem;
use App\Models\RH\TimeEntry;
use App\Models\Tiers\ThirdParty;
use App\Models\Vision3D\BimModel;
use App\Observers\Chantiers\ChantierObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Relaticle\ActivityLog\Concerns\InteractsWithTimeline;
use Relaticle\ActivityLog\Contracts\HasTimeline;
use Relaticle\ActivityLog\Timeline\TimelineBuilder;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([ChantierObserver::class])]
class Chantier extends Model implements HasMedia, HasTimeline
{
    use HasFactory, InteractsWithMedia, InteractsWithTimeline, LogsActivity;

    protected $fillable = [
        'uuid',
        'client_id',
        'manager_id',
        'reference',
        'name',
        'status',
        'address',
        'zip_code',
        'city',
        'latitude',
        'longitude',
        'budget_hours',
        'budget_material',
        'budget_subcontracting',
        'budget_total_ht',
        'start_date_preview',
        'end_date_preview',
        'start_date',
        'end_date',
        'quote_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id')
            ->where('type', ThirdPartyType::CLIENT);
    }

    public function virtualWarehouse(): HasOne
    {
        return $this->hasOne(Warehouse::class, 'chantier_id');
    }

    public function stocks(): HasManyThrough
    {
        return $this->hasManyThrough(
            Stock::class,
            Warehouse::class,
            'chantier_id', // Foreign key on the environments table...
            'warehouse_id', // Foreign key on the deployments table...
            'id', // Local key on the projects table...
            'id' // Local key on the environments table...
        );
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class, 'quote_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CustomerInvoice::class, 'chantier_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'chantier_members');
    }

    public function subcontractors(): BelongsToMany
    {
        return $this->belongsToMany(ThirdParty::class, 'chantier_subcontractors')
            ->where('type', ThirdPartyType::SUBCONTRACTOR);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ChantierPhase::class)->orderBy('order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ChantierLog::class)->latest();
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    public function internalRentalInvoices(): HasMany
    {
        return $this->hasMany(InternalRentalInvoice::class);
    }

    public function rentalContracts(): HasMany
    {
        return $this->hasMany(RentalContract::class, 'chantier_id');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function reserves(): HasMany
    {
        return $this->hasMany(ChantierReserve::class);
    }

    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }

    /**
     * Les maquettes 3D / plans BIM de ce chantier
     */
    public function bimModels(): MorphMany
    {
        return $this->morphMany(BimModel::class, 'modelable');
    }

    public function expenseItems(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function doeDocuments(): HasMany
    {
        return $this->hasMany(DoeDocument::class);
    }

    public function weatherAlerts(): HasMany
    {
        return $this->hasMany(WeatherAlert::class);
    }

    public function cibtpDeclarations(): HasMany
    {
        return $this->hasMany(CibtpDeclaration::class);
    }

    protected function casts(): array
    {
        return [
            'status' => ChantierStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'start_date_preview' => 'date',
            'end_date_preview' => 'date',
            'latitude' => 'float',
            'longitude' => 'float',
            'budget_hours' => 'decimal:2',
            'budget_material' => 'decimal:2',
            'budget_subcontracting' => 'decimal:2',
            'budget_total_ht' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->uuid = (string) Str::uuid());
    }

    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->zip_code} {$this->city}";
    }

    public function getLocationAttribute(): array
    {
        return [$this->latitude, $this->longitude];
    }

    public function getRealHoursAttribute(): float
    {
        return (float) $this->timeEntries()
            ->where('status', TimeEntryStatus::APPROVED)
            ->sum('hours');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function timeline(): TimelineBuilder
    {
        return TimelineBuilder::make($this)->fromActivityLog();
    }
}
