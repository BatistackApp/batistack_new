<?php

namespace App\Filament\Tiers\Resources\ThirdParties\Pages;

use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Tiers\Resources\ThirdParties\Actions\GenerateContractAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\PrintAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\SyncFinancialAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\SynchronizeSirenAction;
use App\Filament\Tiers\Resources\ThirdParties\Actions\VigilanceTransfertAction;
use App\Filament\Tiers\Resources\ThirdParties\ThirdPartyResource;
use App\Jobs\Tiers\CollectLegalDocumentsJob;
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
                Action::make('verify_conformity')
                    ->label('Vérifier la conformité')
                    ->visible(fn (ThirdParty $record) => $record->type === ThirdPartyType::SUBCONTRACTOR)
                    ->icon(Phosphor::ShieldCheck)
                    ->action(function (ThirdParty $record) {
                        VerifyGloabVigilanceJob::dispatch($record);

                        Notification::make()
                            ->success()
                            ->title('Vérification de conformité lançé en arrière plan')
                            ->send();
                    }),
                Action::make('collect_documents')
                    ->label('Collecter les documents légaux')
                    ->icon(Phosphor::DownloadSimple)
                    ->color('info')
                    ->visible(fn (ThirdParty $record) => $record->siren && in_array($record->type, [ThirdPartyType::SUBCONTRACTOR, ThirdPartyType::CLIENT]))
                    ->action(function (ThirdParty $record) {
                        CollectLegalDocumentsJob::dispatch($record);

                        Notification::make()
                            ->success()
                            ->title('Collecte des documents légaux lancée en arrière-plan')
                            ->send();
                    }),
                GenerateContractAction::make(),
                VigilanceTransfertAction::make(),
                SyncFinancialAction::make()
                    ->label('Actualiser Solvabilité')
                    ->icon(Phosphor::Bank)
                    ->color('info'),
            ]),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Fiche du tiers: '.$this->record->legal_name;
    }
}
