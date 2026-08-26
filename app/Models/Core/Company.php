<?php

namespace App\Models\Core;

use App\Models\Banque\BankAccount;
use App\Observers\Core\CompanyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @method void prepareToAttachMedia(Media $media, FileAdder $fileAdder)
 */
#[ObservedBy([CompanyObserver::class])]
class Company extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'legal_name',
        'siret',
        'vat_number',
        'share_capital',
        'address',
        'zip_code',
        'city',
        'phone',
        'email',
        'website',
        'iban',
        'bic',
    ];

    protected $casts = [
        'share_capital' => 'decimal:2',
    ];

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('core')
            ->singleFile();
    }
}
