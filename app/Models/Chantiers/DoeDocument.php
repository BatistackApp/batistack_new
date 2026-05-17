<?php

namespace App\Models\Chantiers;

use App\Enums\Chantiers\DoeDocumentCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DoeDocument extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'chantier_id',
        'name',
        'category',
        'is_validated',
        'notes',
    ];

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    protected function casts(): array
    {
        return [
            'is_validated' => 'boolean',
            'category' => DoeDocumentCategory::class,
        ];
    }

    /**
     * Configuration de la collection de médias Spatie.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachment')
            ->singleFile(); // Un document technique par fiche
    }
}
