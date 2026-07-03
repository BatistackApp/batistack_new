<?php

namespace App\Models\Tiers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ThirdPartyDocument extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

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
        return $this->morphMany(\App\Models\Core\Signature::class, 'signable');
    }
}
