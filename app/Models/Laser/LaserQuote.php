<?php

namespace App\Models\Laser;

use App\Enums\Laser\QuoteStatus;
use App\Contracts\Core\Signable;
use App\Models\Core\Signature;
use App\Models\Laser\Concerns\RecalculatesLaserTotals;
use App\Models\Tiers\ThirdParty;
use App\Observers\Laser\LaserQuoteObserver;
use App\Traits\Core\HasSignature;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([LaserQuoteObserver::class])]
class LaserQuote extends Model implements Signable
{
    use HasFactory, HasSignature, RecalculatesLaserTotals;

    protected $fillable = [
        'client_id',
        'reference',
        'status',
        'total_ht',
        'total_ttc',
        'terms',
        'expires_at',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'total_ht' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'expires_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(LaserQuoteLine::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(LaserOrder::class);
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function getSignatureUrl(Signature $signature): ?string
    {
        return Storage::disk('public')->url('documents/laser/quotes/devis_laser_'.$this->reference.'.pdf');
    }

    public function getSignaturePath(): ?string
    {
        return Storage::disk('public')->path('documents/laser/quotes/devis_laser_'.$this->reference.'.pdf');
    }

    protected function getStampedPath(): ?string
    {
        return 'documents/laser/quotes/signes/devis_laser_'.$this->reference.'.pdf';
    }

    protected function getStampedUrlForPath(string $stampedPath): ?string
    {
        return Storage::disk('public')->url(ltrim(str_replace('\\', '/', $stampedPath), '/'));
    }

    public function getSignatoryDisplayName(): ?string
    {
        return $this->client?->name;
    }

    public function onPostSignature(Signature $signature): void
    {
        $this->updateQuietly([
            'status' => QuoteStatus::ACCEPTED,
            'signed_at' => $signature->signed_at ?? now(),
        ]);
    }

    public function canBeDeleted(): bool
    {
        return $this->status->value === QuoteStatus::DRAFT->value;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [QuoteStatus::DRAFT, QuoteStatus::SENT], true)
            && ! $this->signatures()->where('status', \App\Enums\Core\SignatureStatus::SIGNED)->exists();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
