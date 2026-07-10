<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Actions;

use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\PappersService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SyncFinancialAction
{
    public static function make(): Action
    {
        return Action::make('sync_financial')
            ->action(function (ThirdParty $record) {
                $success = app(PappersService::class)->syncFinancialData($record);

                if ($success) {
                    Notification::make()
                        ->title('Données financières actualisées')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Échec de la synchronisation')
                        ->danger()
                        ->send();
                }
            });
    }
}
