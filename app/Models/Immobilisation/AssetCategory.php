<?php

namespace App\Models\Immobilisation;

use App\Enums\Immobilisation\DepreciationMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'account_code',
        'default_depreciation_years',
        'default_method',
    ];

    protected function casts(): array
    {
        return [
            'default_method' => DepreciationMethod::class,
        ];
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }
}
