<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Schemas;

use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\DepreciationMethod;
use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FixedAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('invoice_upload')
                    ->collection('invoices')
                    ->label('Numérisation OCR (Glissez la facture ici)')
                    ->image()
                    ->helperText('Uniquement des images (JPG, PNG). L\'IA remplira automatiquement les champs.')
                    ->live(onBlur: false)
                    ->afterStateUpdated(function (\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $state, \Filament\Forms\Set $set) {
                        if ($state) {
                            $ocrService = app(\App\Services\RH\OcrServiceInterface::class);
                            $data = $ocrService->extractAssetData($state->getRealPath());

                            if (!empty($data['purchase_price'])) {
                                $set('purchase_price', $data['purchase_price']);
                            }
                            if (!empty($data['purchase_date'])) {
                                $set('purchase_date', $data['purchase_date']);
                            }
                            if (!empty($data['merchant'])) {
                                $set('name', $data['merchant']);
                            }
                            if (!empty($data['asset_category_id'])) {
                                $set('asset_category_id', $data['asset_category_id']);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Analyse OCR terminée')
                                ->body('Les données de la facture ont été extraites.')
                                ->success()
                                ->send();
                        }
                    })
                    ->columnSpanFull(),

                Select::make('asset_category_id')
                    ->label('Catégorie d\'actif')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')->label('Nom')
                    ->label('Nom de l\'immobilisation')
                    ->required()
                    ->maxLength(255),
                TextInput::make('serial_number')
                    ->label('Numéro de série')
                    ->maxLength(255),
                DatePicker::make('purchase_date')
                    ->label('Date d\'acquisition')
                    ->required(),
                TextInput::make('purchase_price')
                    ->label('Valeur d\'achat')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                TextInput::make('salvage_value')
                    ->label('Valeur résiduelle')
                    ->numeric()
                    ->default(0)
                    ->prefix('€'),
                Select::make('depreciation_method')
                    ->label('Méthode d\'amortissement')
                    ->options(DepreciationMethod::class)
                    ->required()
                    ->default(DepreciationMethod::LINEAR),
                TextInput::make('useful_life_years')
                    ->label('Durée d\'amortissement (années)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(5),
                Section::make('Suivi et Réglementation')
                    ->schema([
                        Select::make('status')->label('Statut')
                            ->label('Statut')
                            ->options(AssetStatus::class)
                            ->default(AssetStatus::ACTIVE)
                            ->required(),
                        TextInput::make('vgp_frequency_months')
                            ->label('Fréquence VGP (mois)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Laissez vide si l\'équipement n\'est pas soumis aux VGP.')
                            ->prefixIcon('heroicon-o-shield-check'),
                    ]),
                Section::make('Subvention / Financement')
                    ->schema([
                        TextInput::make('grant_amount')
                            ->label('Montant de la subvention')
                            ->numeric()
                            ->default(0)
                            ->prefix('€')
                            ->helperText('La subvention sera étalée au même rythme que l\'amortissement (Norme PCG).'),
                        TextInput::make('grant_name')
                            ->label('Origine de la subvention (Ex: BPI, Région)')
                            ->maxLength(255),
                    ])->columns(2),
                Select::make('supplier_invoice_id')
                    ->label('Facture d\'achat liée')
                    ->relationship('supplierInvoice', 'reference')
                    ->searchable()
                    ->preload(),
                Select::make('vehicle_id')
                    ->label('Véhicule lié')
                    ->relationship('vehicle', 'license_plate')
                    ->searchable()
                    ->preload(),
                Select::make('chantier_id')->label('Chantier')
                    ->label('Chantier d\'imputation analytique')
                    ->relationship('chantier', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
