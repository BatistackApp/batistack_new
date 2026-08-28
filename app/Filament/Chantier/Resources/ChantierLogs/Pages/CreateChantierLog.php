<?php

namespace App\Filament\Chantier\Resources\ChantierLogs\Pages;

use App\Filament\Chantier\Resources\ChantierLogs\ChantierLogResource;
use App\Models\Chantiers\ChantierLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateChantierLog extends CreateRecord
{
    protected static string $resource = ChantierLogResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['user_id'] = auth()->user()->id;

        return ChantierLog::create($data);
    }
}
