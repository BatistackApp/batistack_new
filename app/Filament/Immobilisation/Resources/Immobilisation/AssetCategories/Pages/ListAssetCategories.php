<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\AssetCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetCategories extends ListRecords
{
    protected static string $resource = AssetCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle catégorie'),
        ];
    }
}
