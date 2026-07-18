<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\AssetCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetCategory extends CreateRecord
{
    protected static string $resource = AssetCategoryResource::class;
}
