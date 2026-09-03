<?php

namespace App\Models\Core;

use App\Enums\Core\SignatureStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureSigner extends Model
{
    use HasFactory;

    protected $fillable = [
        'signature_id',
        'name',
        'email',
        'user_id',
        'role',
        'status',
        'token',
        'signature_data',
        'ip_address',
        'signed_at',
        'metadata',
    ];

    protected $casts = [
        'status' => SignatureStatus::class,
        'signed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function signature(): BelongsTo
    {
        return $this->belongsTo(Signature::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === SignatureStatus::PENDING;
    }

    public function getIsSignedAttribute(): bool
    {
        return $this->status === SignatureStatus::SIGNED;
    }

    public function getIsRefusedAttribute(): bool
    {
        return $this->status === SignatureStatus::REFUSED;
    }
}
