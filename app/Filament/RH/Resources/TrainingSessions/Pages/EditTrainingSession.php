<?php

namespace App\Filament\RH\Resources\TrainingSessions\Pages;

use App\Filament\RH\Resources\TrainingSessions\TrainingSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrainingSession extends EditRecord
{
    protected static string $resource = TrainingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
