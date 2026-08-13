<?php

namespace App\Filament\RH\Resources\TrainingSessions\Pages;

use App\Filament\RH\Resources\TrainingSessions\TrainingSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainingSessions extends ListRecords
{
    protected static string $resource = TrainingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
