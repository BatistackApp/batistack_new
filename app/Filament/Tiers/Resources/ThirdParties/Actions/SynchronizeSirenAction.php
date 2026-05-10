<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Actions;

use App\Jobs\Tiers\SynchronizeSirenJob;
use App\Models\Tiers\ThirdParty;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SynchronizeSirenAction
{
    public static function make()
    {
        return Action::make('synchronize')
            ->label('Synchroniser SIREN')
            ->icon(Phosphor::ArrowArcLeft)
            ->action(function (ThirdParty $record) {
                SynchronizeSirenJob::dispatch($record);

                Notification::make()
                    ->success()
                    ->title('Synchronisation lancée en arrière plans')
                    ->send();
            });
    }
}
