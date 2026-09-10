<?php

namespace App\Filament\Laser\Actions;

use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserQuote;
use App\Models\Laser\LaserQuoteLine;
use App\Services\Laser\DxfParserService;
use App\Services\Laser\LaserQuoteService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
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
                    ->acceptedFileTypes(['application/dxf', 'application/x-dxf', 'text/plain'])
                    ->helperText('Formats acceptés : .dxf (AutoCAD DXF). Les dimensions du fichier doivent être en millimètres.')
                    ->required()
                    ->maxSize(10240)
                    ->storeFiles(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state === null) {
                            $set('dxf_preview', null);
                            $set('dxf_length', null);
                            $set('dxf_width', null);
                            $set('dxf_cut_length', null);
                            $set('dxf_entity_count', null);
                            $set('dxf_layers', null);

                            return;
                        }

                        /** @var \Illuminate\Http\UploadedFile $file */
                        $file = $state;
                        $content = file_get_contents($file->getRealPath());

                        if ($content === false) {
                            $set('dxf_preview', 'Erreur de lecture du fichier.');
                            $set('dxf_length', null);
                            $set('dxf_width', null);
                            $set('dxf_cut_length', null);
                            $set('dxf_entity_count', null);
                            $set('dxf_layers', null);

                            return;
                        }

                        /** @var DxfParserService $parser */
                        $parser = app(DxfParserService::class);
                        $allowedLayers = config('laser.dxf_cut_layers', []);
                        $result = $parser->parse($content, $allowedLayers);

                        if (! $result->isValid()) {
                            $set('dxf_preview', 'Erreur : '.$result->error);
                            $set('dxf_length', null);
                            $set('dxf_width', null);
                            $set('dxf_cut_length', null);
                            $set('dxf_entity_count', null);
                            $set('dxf_layers', null);

                            return;
                        }

                        $set('dxf_length', $result->lengthMm);
                        $set('dxf_width', $result->widthMm);
                        $set('dxf_cut_length', $result->totalCutLengthMm);
                        $set('dxf_entity_count', $result->entityCount);
                        $set('dxf_layers', implode(', ', $result->layers));
                        $set('dxf_preview', 'Fichier analysé avec succès.');
                    }),

                Placeholder::make('dxf_preview')
                    ->label('Résultat de l\'analyse')
                    ->content(fn (callable $get) => $get('dxf_preview') ?? 'En attente du fichier...'),

                Placeholder::make('dxf_length')
                    ->label('Longueur extraite (mm)')
                    ->content(fn (callable $get) => $get('dxf_length') !== null ? number_format($get('dxf_length'), 2, ',', ' ').' mm' : '-'),

                Placeholder::make('dxf_width')
                    ->label('Largeur extraite (mm)')
                    ->content(fn (callable $get) => $get('dxf_width') !== null ? number_format($get('dxf_width'), 2, ',', ' ').' mm' : '-'),

                Placeholder::make('dxf_cut_length')
                    ->label('Périmètre de découpe (mm)')
                    ->content(fn (callable $get) => $get('dxf_cut_length') !== null ? number_format($get('dxf_cut_length'), 2, ',', ' ').' mm' : '-'),

                Placeholder::make('dxf_entity_count')
                    ->label('Nombre d\'entités')
                    ->content(fn (callable $get) => $get('dxf_entity_count') !== null ? (string) $get('dxf_entity_count') : '-'),

                Placeholder::make('dxf_layers')
                    ->label('Couches détectées')
                    ->content(fn (callable $get) => $get('dxf_layers') ?? '-'),

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

                $service = app(LaserQuoteService::class);
                $quantity = (int) ($data['quantity'] ?? 1);
                $discount = $service->applyDiscount($quantity);

                LaserQuoteLine::create([
                    'laser_quote_id' => $quote->id,
                    'material_id' => $material->id,
                    'description' => $data['description'] ?? null,
                    'length_mm' => $result->lengthMm,
                    'width_mm' => $result->widthMm,
                    'thickness_mm' => $data['thickness_mm'],
                    'quantity' => $quantity,
                    'cut_length_mm' => $result->totalCutLengthMm,
                    'programming_cost' => $data['programming_cost'] ?? 0,
                    'price_per_kg' => $material->price_per_kg,
                    'price_per_meter' => $material->price_per_meter,
                    'density_kg_m3' => $material->density_kg_m3,
                    'discount_pct' => $discount,
                    'total_ht' => 0,
                ]);

                Notification::make()
                    ->title('Ligne créée depuis DXF')
                    ->body("Longueur : {$result->lengthMm} mm | Largeur : {$result->widthMm} mm | Périmètre : {$result->totalCutLengthMm} mm")
                    ->success()
                    ->send();
            });
    }
}
