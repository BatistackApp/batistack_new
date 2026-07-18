<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\FixedAssetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFixedAsset extends ViewRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('inventory')
                ->label('✅ Valider la présence')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Inventaire Physique')
                ->modalDescription('Confirmez-vous que ce matériel est bien présent et en bon état ?')
                ->action(function () {
                    $record = $this->getRecord();
                    $record->update(['last_inventoried_at' => now()]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Inventaire mis à jour')
                        ->success()
                        ->send();
                }),
            \Filament\Actions\Action::make('report_breakdown')
                ->label('Signaler en panne')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->getRecord();
                    $record->update(['status' => \App\Enums\Immobilisation\AssetStatus::IN_MAINTENANCE]);
                    \Filament\Notifications\Notification::make()->title('Machine déclarée en panne')->danger()->send();
                })
                ->visible(fn () => $this->getRecord()->status !== \App\Enums\Immobilisation\AssetStatus::IN_MAINTENANCE),
            \Filament\Actions\Action::make('log_repair')
                ->label('Saisir Facture Réparation')
                ->icon('heroicon-o-wrench')
                ->color('warning')
                ->form(fn (\Filament\Forms\Form $form) => \App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Schemas\AssetMaintenanceForm::configure($form))
                ->action(function (array $data) {
                    $record = $this->getRecord();
                    $record->maintenances()->create($data);
                    
                    if ($record->status === \App\Enums\Immobilisation\AssetStatus::IN_MAINTENANCE) {
                        $record->update(['status' => \App\Enums\Immobilisation\AssetStatus::ACTIVE]);
                        \Filament\Notifications\Notification::make()->title('Réparation enregistrée, machine à nouveau active')->success()->send();
                    } else {
                        \Filament\Notifications\Notification::make()->title('Intervention enregistrée')->success()->send();
                    }
                }),
            \Filament\Actions\Action::make('exceptional_impairment')
                ->label('Dépréciation Exceptionnelle')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Déclarer une perte de valeur')
                ->modalDescription('Attention, cette action va réduire la Valeur Nette Comptable de l\'actif et recalculer son plan d\'amortissement de façon permanente.')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('date')
                        ->label('Date de constatation')
                        ->default(now())
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Montant de la dépréciation (HT)')
                        ->numeric()
                        ->prefix('€')
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('reason')
                        ->label('Motif (Casse, Sinistre...)')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data, \App\Services\Immobilisation\AssetImpairmentService $service) {
                    $record = $this->getRecord();
                    $service->recordImpairment($record, $data);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Dépréciation enregistrée')
                        ->body('Le plan d\'amortissement a été recalculé avec succès.')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->getRecord()->status === \App\Enums\Immobilisation\AssetStatus::ACTIVE && $this->getRecord()->depreciation_method !== \App\Enums\Immobilisation\DepreciationMethod::NONE),
            \Filament\Actions\Action::make('print_sheet')
                ->label('Imprimer Fiche')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function () {
                    $record = $this->getRecord();
                    $service = new \App\Services\Immobilisation\ImmobilisationDocumentService();
                    $path = $service->generateAssetSheet($record);
                    return response()->download($path);
                }),
            EditAction::make(),
        ];
    }
}
