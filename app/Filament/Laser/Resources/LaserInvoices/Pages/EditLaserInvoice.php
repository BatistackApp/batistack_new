<?php

namespace App\Filament\Laser\Resources\LaserInvoices\Pages;

use App\Enums\Laser\InvoiceStatus;
use App\Filament\Laser\Resources\LaserInvoices\LaserInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;

class EditLaserInvoice extends EditRecord
{
    use HasPageHeader;

    protected static string $resource = LaserInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === InvoiceStatus::DRAFT),
        ];
    }
}
