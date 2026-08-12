<?php

namespace App\Filament\RH\Resources\RH\Interviews\Pages;

use App\Filament\RH\Resources\RH\Interviews\InterviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInterview extends EditRecord
{
    protected static string $resource = InterviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
