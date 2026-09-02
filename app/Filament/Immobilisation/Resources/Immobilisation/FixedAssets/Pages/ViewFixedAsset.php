<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Pages;

use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\DepreciationMethod;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Schemas\AssetMaintenanceForm;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\FixedAssetResource;
use App\Services\Immobilisation\AssetDisposalService;
use App\Services\Immobilisation\AssetImpairmentService;
use App\Services\Immobilisation\ImmobilisationDocumentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewFixedAsset extends ViewRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_sheet')
                ->label('Imprimer Fiche')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function () {
                    $record = $this->getRecord();
                    $service = new ImmobilisationDocumentService;
                    $path = $service->generateAssetSheet($record);

                    return $service->download($path);
                }),
            EditAction::make(),
            ActionGroup::make([
                    Action::make('inventory')
                        ->label('✅ Valider la présence')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Inventaire Physique')
                        ->modalDescription('Confirmez-vous que ce matériel est bien présent et en bon état ?')
                        ->action(function () {
                            $record = $this->getRecord();
                            $record->update(['last_inventoried_at' => now()]);

                            Notification::make()
                                ->title('Inventaire mis à jour')
                                ->success()
                                ->send();
                        }),
                    Action::make('report_breakdown')
                        ->label('Signaler en panne')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function () {
                            $record = $this->getRecord();
                            $record->update(['status' => AssetStatus::IN_MAINTENANCE]);
                            Notification::make()->title('Machine déclarée en panne')->danger()->send();
                        })
                        ->visible(fn () => $this->getRecord()->status !== AssetStatus::IN_MAINTENANCE),
                    Action::make('log_repair')
                        ->label('Saisir Facture Réparation')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->schema(fn (Schema $form) => AssetMaintenanceForm::configure($form, true))
                        ->action(function (array $data) {
                            $record = $this->getRecord();
                            $record->maintenances()->create($data);

                            if ($record->status === AssetStatus::IN_MAINTENANCE) {
                                $record->update(['status' => AssetStatus::ACTIVE]);
                                Notification::make()->title('Réparation enregistrée, machine à nouveau active')->success()->send();
                            } else {
                                Notification::make()->title('Intervention enregistrée')->success()->send();
                            }
                        }),
                    Action::make('exceptional_impairment')
                        ->label('Dépréciation Exceptionnelle')
                        ->icon('heroicon-o-arrow-trending-down')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Déclarer une perte de valeur')
                        ->modalDescription('Attention, cette action va réduire la Valeur Nette Comptable de l\'actif et recalculer son plan d\'amortissement de façon permanente.')
                        ->schema([
                            DatePicker::make('date')->label('Date')
                                ->label('Date de constatation')
                                ->default(now())
                                ->required(),
                            TextInput::make('amount')->label('Montant')
                                ->label('Montant de la dépréciation (HT)')
                                ->numeric()
                                ->prefix('€')
                                ->required(),
                            TextInput::make('reason')
                                ->label('Motif (Casse, Sinistre...)')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, AssetImpairmentService $service) {
                            $record = $this->getRecord();
                            $service->recordImpairment($record, $data);

                            Notification::make()
                                ->title('Dépréciation enregistrée')
                                ->body('Le plan d\'amortissement a été recalculé avec succès.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => $this->getRecord()->status === AssetStatus::ACTIVE && $this->getRecord()->depreciation_method !== DepreciationMethod::NONE),
                    Action::make('dispose')
                        ->label('Céder / Rebut')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Sortie définitive de l\'actif')
                        ->modalDescription('Cette action va définitivement sortir l\'actif du parc et générer les écritures comptables correspondantes.')
                        ->schema([
                            DatePicker::make('disposal_date')->label('Date de sortie')->required()->default(now()),
                            TextInput::make('sale_price')->label('Prix de cession')->numeric()->default(0)->required()->prefix('€'),
                            TextInput::make('reason')->label('Motif (Revente, Vol, Rebut)')->required(),
                        ])
                        ->action(function (array $data) {
                            $record = $this->getRecord();
                            $service = app(AssetDisposalService::class);
                            $service->dispose($record, $data['disposal_date'], $data['sale_price'], $data['reason']);

                            Notification::make()
                                ->title('Actif cédé')
                                ->body('L\'actif a été sorti du parc et les écritures comptables ont été générées.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => $this->getRecord()->status !== AssetStatus::DISPOSED),
                ]),
        ];
    }
}
