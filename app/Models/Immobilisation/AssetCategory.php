<?php

namespace App\Models\Immobilisation;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

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
            'default_method' => \App\Enums\Immobilisation\DepreciationMethod::class,
        ];
    }

    public function fixedAssets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }
}
