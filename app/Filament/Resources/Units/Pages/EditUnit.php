<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected static ?string $breadcrumb = 'Edition';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getRecordTitle(): string|Htmlable
    {
        return $this->record->name;
    }
}
