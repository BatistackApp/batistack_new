<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Actions;

use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\TiersDocumentService;
use Filament\Actions\Action;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PrintAction
{
    public static function make(string $type): Action
    {
        return match ($type) {
            'list' => Action::make('print_list')
                ->label('Imprimer la liste')
                ->icon(Phosphor::Printer)
                ->color('info')
                ->action(function (TiersDocumentService $service) {
                    $path = $service->generateList(ThirdParty::all());

                    return response()->download($path);
                }),

            'details' => Action::make('print_details')
                ->label('Imprimer la fiche')
                ->icon(Phosphor::Printer)
                ->color('info')
                ->action(function (TiersDocumentService $service, ThirdParty $record) {
                    $path = $service->generateDetails($record);

                    return response()->download($path);
                }),
        };

    }
}
