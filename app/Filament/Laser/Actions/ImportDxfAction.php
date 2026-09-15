<?php

namespace App\Filament\Laser\Actions;

use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserQuote;
use App\Models\Laser\LaserQuoteLine;
use App\Services\Laser\DxfParserService;
use App\Services\Laser\LaserQuoteService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ImportDxfAction
{
    public static function make(LaserQuote $quote): Action
    {
        return Action::make('import_dxf')
            ->label('Importer DXF')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->modalHeading('Importer un fichier DXF')
            ->modalDescription('Extrait automatiquement les dimensions et le périmètre de découpe du fichier CAD. Les dimensions doivent être en millimètres.')
            ->modalSubmitActionLabel('Créer la ligne')
            ->form([
                FileUpload::make('dxf_file')
                    ->label('Fichier DXF')
                    ->helperText('Formats acceptés : .dxf (AutoCAD DXF). Les dimensions du fichier doivent être en millimètres.')
                    ->required()
                    ->maxSize(10240)
                    ->storeFiles(false),

                Select::make('material_id')
                    ->label('Matériau')
                    ->options(fn () => LaserMaterial::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($get, $set) {
                        $material = LaserMaterial::where('id', $get('material_id'))->where('is_active', true)->first();
                        if ($material) {
                            $set('price_per_kg', $material->price_per_kg);
                            $set('price_per_meter', $material->price_per_meter);
                            $set('density_kg_m3', $material->density_kg_m3);
                        }
                    }),

                TextInput::make('thickness_mm')
                    ->label('Épaisseur (mm)')
                    ->numeric()
                    ->required()
                    ->minValue(0.1),

                TextInput::make('quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(1),

                TextInput::make('programming_cost')
                    ->label('Coût fixe programmation (€)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                TextInput::make('description')
                    ->label('Description'),

                TextInput::make('price_per_kg')
                    ->label('Prix/kg (€)')
                    ->numeric()
                    ->hidden()
                    ->dehydrated(true),

                TextInput::make('price_per_meter')
                    ->label('Prix/m (€)')
                    ->numeric()
                    ->hidden()
                    ->dehydrated(true),

                TextInput::make('density_kg_m3')
                    ->label('Densité')
                    ->numeric()
                    ->hidden()
                    ->dehydrated(true),
            ])
            ->action(function (array $data) use ($quote): void {
                $file = $data['dxf_file'] ?? null;
                if (! $file) {
                    Notification::make()
                        ->title('Fichier DXF manquant')
                        ->danger()
                        ->send();

                    return;
                }

                $content = file_get_contents($file->getRealPath());
                if ($content === false) {
                    Notification::make()
                        ->title('Erreur de lecture du fichier')
                        ->danger()
                        ->send();

                    return;
                }

                $parser = app(DxfParserService::class);
                $allowedLayers = config('laser.dxf_cut_layers', []);
                $result = $parser->parse($content, $allowedLayers);

                if (! $result->isValid()) {
                    Notification::make()
                        ->title('Erreur d\'analyse DXF')
                        ->body($result->error)
                        ->danger()
                        ->send();

                    return;
                }

                $material = LaserMaterial::where('id', $data['material_id'])->where('is_active', true)->first();
                if (! $material) {
                    Notification::make()
                        ->title('Matériau introuvable ou inactif')
                        ->danger()
                        ->send();

                    return;
                }

                $thickness = (float) ($data['thickness_mm'] ?? 0);
                if ($thickness <= 0) {
                    Notification::make()
                        ->title('Épaisseur invalide')
                        ->body('L\'épaisseur doit être strictement positive.')
                        ->danger()
                        ->send();

                    return;
                }

                if ($material->min_thickness_mm !== null && $thickness < $material->min_thickness_mm) {
                    Notification::make()
                        ->title('Épaisseur trop faible')
                        ->body("L'épaisseur minimale pour {$material->name} est {$material->min_thickness_mm} mm.")
                        ->danger()
                        ->send();

                    return;
                }

                if ($material->max_thickness_mm !== null && $thickness > $material->max_thickness_mm) {
                    Notification::make()
                        ->title('Épaisseur trop élevée')
                        ->body("L'épaisseur maximale pour {$material->name} est {$material->max_thickness_mm} mm.")
                        ->danger()
                        ->send();

                    return;
                }

                $quantity = (int) ($data['quantity'] ?? 1);
                if ($quantity < 1) {
                    Notification::make()
                        ->title('Quantité invalide')
                        ->body('La quantité doit être au moins 1.')
                        ->danger()
                        ->send();

                    return;
                }

                $service = app(LaserQuoteService::class);
                $discount = $service->applyDiscount($quantity);

                LaserQuoteLine::create([
                    'laser_quote_id' => $quote->id,
                    'material_id' => $material->id,
                    'description' => $data['description'] ?? null,
                    'length_mm' => $result->lengthMm,
                    'width_mm' => $result->widthMm,
                    'thickness_mm' => $thickness,
                    'quantity' => $quantity,
                    'cut_length_mm' => $result->totalCutLengthMm,
                    'programming_cost' => max(0.0, (float) ($data['programming_cost'] ?? 0)),
                    'price_per_kg' => $material->price_per_kg,
                    'price_per_meter' => $material->price_per_meter,
                    'density_kg_m3' => $material->density_kg_m3,
                    'discount_pct' => $discount,
                    'total_ht' => 0,
                    'dxf_entities' => $result->entities,
                ]);

                Notification::make()
                    ->title('Ligne créée depuis DXF')
                    ->body("Longueur : {$result->lengthMm} mm | Largeur : {$result->widthMm} mm | Périmètre : {$result->totalCutLengthMm} mm")
                    ->success()
                    ->send();
            });
    }
}
