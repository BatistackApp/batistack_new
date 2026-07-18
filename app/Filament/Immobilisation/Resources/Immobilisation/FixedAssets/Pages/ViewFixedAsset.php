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
