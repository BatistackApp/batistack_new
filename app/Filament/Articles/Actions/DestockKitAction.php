<?php

namespace App\Filament\Articles\Actions;

use App\Models\Articles\Item;
use App\Models\Articles\Warehouse;
use App\Services\Articles\StockService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class DestockKitAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'destock_kit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Préparer un Kit (Déstockage)')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->color('success')
            ->modalHeading('Déstocker un Kit vers une Camionnette')
            ->modalDescription('Sélectionnez le Kit à préparer. Ses composants seront automatiquement transférés vers le dépôt sélectionné.')
            ->form([
                Select::make('kit_id')
                    ->label('Kit / Ouvrage')
                    ->options(function () {
                        return Item::composed()
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),

                TextInput::make('quantity')
                    ->label('Quantité de Kits')
                    ->numeric()
                    ->default(1)
                    ->minValue(0.01)
                    ->required(),

                Select::make('from_warehouse_id')
                    ->label('Depuis l\'Entrepôt Source')
                    ->options(Warehouse::pluck('name', 'id'))
                    ->default(function () {
                        // Par défaut, l'entrepôt principal s'il existe
                        $main = Warehouse::where('is_active', true)->first();
                        return $main?->id;
                    })
                    ->searchable()
                    ->required(),

                Select::make('to_warehouse_id')
                    ->label('Vers la Camionnette (Destination)')
                    ->options(function () {
                        return Warehouse::where('is_active', true)
                            ->where('name', 'like', '%camionnette%') // Vous pouvez ajuster le filtre selon votre modèle (ex: where('type', 'vehicle'))
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, StockService $stockService) {
                try {
                    $kit = Item::findOrFail($data['kit_id']);
                    $from = Warehouse::findOrFail($data['from_warehouse_id']);
                    $to = Warehouse::findOrFail($data['to_warehouse_id']);
                    $quantity = (float) $data['quantity'];

                    $stockService->transferKit($kit, $from, $to, $quantity);

                    Notification::make()
                        ->success()
                        ->title('Kit préparé avec succès !')
                        ->body("Les composants de {$kit->name} ont été transférés vers {$to->name}.")
                        ->send();
                        
                } catch (\App\Exceptions\Articles\ArticlesModuleException $e) {
                    Notification::make()
                        ->danger()
                        ->title('Erreur lors du déstockage')
                        ->body($e->getMessage())
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title('Erreur inattendue')
                        ->body('Une erreur technique est survenue.')
                        ->send();
                    report($e);
                }
            });
    }
}
