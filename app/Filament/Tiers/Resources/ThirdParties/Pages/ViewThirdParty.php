<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Pages;

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Tiers\Resources\ThirdParties\Actions\GenerateContractAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\PrintAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\SynchronizeSirenAction;
use App\Filament\Tiers\Resources\ThirdParties\ThirdPartyResource;
use App\Jobs\Tiers\VerifyGloabVigilanceJob;
use App\Models\Tiers\ThirdParty;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewThirdParty extends ViewRecord
{
    protected static string $resource = ThirdPartyResource::class;

    protected static ?string $breadcrumb = 'Fiche';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            PrintAction::make('details'),
            ActionGroup::make([
                SynchronizeSirenAction::make(),
                Action::make('veirufy_conformity')
                    ->label('Vérifier la conformité')
                    ->visible(fn (ThirdParty $record) => $record->type->value === ThirdPartyType::SUBCONTRACTOR->value)
                    ->icon(Phosphor::ShieldCheck)
                    ->action(function (ThirdParty $record) {
                        VerifyGloabVigilanceJob::dispatch($record);

                        Notification::make()
                            ->success()
                            ->title('Vérification de conformité lançé en arrière plan')
                            ->send();
                    }),
                GenerateContractAction::make(),
            ]),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Fiche du tiers: '.$this->record->legal_name;
    }
}
