<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class GeneratedDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'module',
        'type',
        'entity_type',
        'entity_id',
        'file_path',
        'file_disk',
        'file_name',
        'file_size',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    // ============================================
    // RELATIONS
    // ============================================

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function entity()
    {
        return $this->morphTo();
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForEntity(Builder $query, string $entityType, int $entityId): Builder
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('file_name', 'like', "%{$term}%")
            ->orWhere('module', 'like', "%{$term}%")
            ->orWhere('type', 'like', "%{$term}%");
    }

    // ============================================
    // METHODS
    // ============================================

    public function temporaryUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        if ($this->file_disk === 's3') {
            return Storage::disk($this->file_disk)->temporaryUrl($this->file_path, now()->addMinutes(5));
        }

        return Storage::disk($this->file_disk)->url($this->file_path);
    }

    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::disk($this->file_disk)->exists($this->file_path)) {
            Storage::disk($this->file_disk)->delete($this->file_path);
        }

        return $this->delete();
    }

    public function getFormattedSizeAttribute(): string
    {
        if (! $this->file_size) {
            return '—';
        }

        $bytes = $this->file_size;
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }

    public function getModuleLabelAttribute(): string
    {
        return match ($this->module) {
            'commerce' => 'Commerce',
            'rh' => 'Ressources Humaines',
            'chantiers' => 'Chantiers',
            'tiers' => 'Tiers',
            'gpao' => 'GPAO',
            'flottes' => 'Flottes',
            'immobilisations' => 'Immobilisations',
            'interventions' => 'Interventions',
            'articles' => 'Articles',
            default => ucfirst($this->module),
        };
    }

    public function getModuleColorAttribute(): string
    {
        return match ($this->module) {
            'commerce' => 'info',
            'rh' => 'success',
            'chantiers' => 'warning',
            'tiers' => 'primary',
            'gpao' => 'gray',
            'flottes' => 'danger',
            'immobilisations' => 'success',
            'interventions' => 'warning',
            'articles' => 'info',
            default => 'gray',
        };
    }
}
