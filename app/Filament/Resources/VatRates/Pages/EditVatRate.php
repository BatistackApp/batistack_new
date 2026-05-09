<?php

namespace App\Filament\Resources\VatRates\Pages;

use App\Filament\Resources\VatRates\VatRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditVatRate extends EditRecord
{
    protected static string $resource = VatRateResource::class;

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
