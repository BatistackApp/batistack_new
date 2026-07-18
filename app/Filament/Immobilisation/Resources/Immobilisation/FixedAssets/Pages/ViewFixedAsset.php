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
