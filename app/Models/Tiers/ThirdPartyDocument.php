<?php

namespace App\Models\Tiers;

use App\Contracts\Core\Signable;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Models\Core\Signature;
use App\Traits\Core\HasSignature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ThirdPartyDocument extends Model implements HasMedia, Signable
{
    use HasFactory, HasSignature, InteractsWithMedia;

    protected $fillable = [
        'third_party_id',
        'type',
        'expiration_date',
        'status',
        'docuseal_submission_id',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ThirdPartyDocumentType::class,
            'status' => ThirdPartyDocumentStatus::class,
            'expiration_date' => 'date',
            'signed_at' => 'datetime',
        ];
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    public function signatures()
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function getSignatureUrl(Signature $signature): ?string
    {
        return $this->getFirstMediaUrl('third_party_documents');
    }

    public function getSignaturePath(): ?string
    {
        return $this->getFirstMedia('third_party_documents')?->getPath();
    }

    public function getSignatoryDisplayName(): ?string
    {
        return $this->thirdParty->name ?? null;
    }

    public function onPostSignature(Signature $signature): void
    {
        $this->update([
            'status' => ThirdPartyDocumentStatus::VALID,
            'signed_at' => now(),
        ]);
    }

    protected function getSignatureMediaCollection(): ?string
    {
        return 'third_party_documents';
    }
}
