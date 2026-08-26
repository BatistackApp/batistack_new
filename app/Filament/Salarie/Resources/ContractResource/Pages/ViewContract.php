<?php

namespace App\Filament\Salarie\Resources\ContractResource\Pages;

use App\Filament\Salarie\Resources\ContractResource;
use App\Models\RH\Contract;
use App\Services\RH\RHDocumentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Télécharger le contrat')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function (Contract $record) {
                    try {
                        $service = app(RHDocumentService::class);
                        $path = $service->generateContract($record);

                        return response()->download(storage_path("app/{$path}"));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Erreur lors de la génération du PDF')
                            ->body($e->getMessage())
                            ->send();

                        return null;
                    }
                }),
        ];
    }
}
