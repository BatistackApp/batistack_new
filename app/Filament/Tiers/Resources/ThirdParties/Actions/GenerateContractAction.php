<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Actions;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Services\Tiers\TiersDocumentService;
use Filament\Actions\Action;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class GenerateContractAction
{
    public static function make(): Action
    {
        return Action::make('generate_contract')
            ->label('Générer un contrat de sous traitant')
            ->icon(Phosphor::FileText)
            ->visible(fn (ThirdParty $record) => $record->type->value === ThirdPartyType::SUBCONTRACTOR->value)
            ->action(function (ThirdParty $record, TiersDocumentService $service) {
                $path = $service->generateContract($record);

                return response()->download($path);
            });
    }
}
