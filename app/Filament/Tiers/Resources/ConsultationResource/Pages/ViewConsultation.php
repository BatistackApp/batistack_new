<?php

namespace App\Filament\Tiers\Resources\ConsultationResource\Pages;

use App\Filament\Tiers\Resources\ConsultationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewConsultation extends ViewRecord
{
    protected static string $resource = ConsultationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
