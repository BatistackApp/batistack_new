<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\RelationManagers;

use App\Enums\RH\ExpenseReportStatus;
use App\Services\RH\GoogleCloudVisionOcrService;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->status !== ExpenseReportStatus::DRAFT;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('chantier_id')
                    ->label('Chantier')
                    ->relationship('chantier', 'name'),
                Select::make('category')
                    ->label('Catégorie')
                    ->options([
                        'Carburant' => 'Carburant',
                        'Péage' => 'Péage',
                        'Parking' => 'Parking',
                        'Repas' => 'Repas',
                        'Hébergement' => 'Hébergement',
                        'Autre' => 'Autre',
                    ])
                    ->live()
                    ->required(),
                Select::make('vehicle_id')
                    ->label('Véhicule')
                    ->relationship('vehicle', 'license_plate')
                    ->visible(fn (Get $get) => in_array($get('category'), ['Carburant', 'Péage', 'Parking']))
                    ->required(fn (Get $get) => in_array($get('category'), ['Carburant', 'Péage', 'Parking'])),
                SpatieMediaLibraryFileUpload::make('receipts')
                    ->label('Preuve (Ticket)')
                    ->collection('receipts')
                    ->image()
                    ->columnSpanFull()
                    ->live(onBlur: false)
                    ->afterStateUpdated(function ($state, Set $set) {
                        if (empty($state)) {
                            return;
                        }

                        $file = is_array($state) ? array_values($state)[0] ?? null : $state;
                        if (! $file || ! method_exists($file, 'getRealPath')) {
                            return;
                        }

                        $path = $file->getRealPath();

                        try {
                            $ocrService = app(GoogleCloudVisionOcrService::class);
                            $extractedData = $ocrService->extractData($path);

                            if (! empty($extractedData['amount_ttc'])) {
                                $set('amount_ttc', $extractedData['amount_ttc']);
                            }
                            if (! empty($extractedData['amount_ht'])) {
                                $set('amount_ht', $extractedData['amount_ht']);
                            }
                            if (! empty($extractedData['vat_amount'])) {
                                $set('vat_amount', $extractedData['vat_amount']);
                            }
                            if (! empty($extractedData['date'])) {
                                $set('date', Carbon::parse($extractedData['date'])->format('Y-m-d'));
                            }
                            if (! empty($extractedData['merchant'])) {
                                $set('merchant', $extractedData['merchant']);
                            }
                            if (! empty($extractedData['category'])) {
                                $set('category', $extractedData['category']);
                            }

                            Notification::make()
                                ->success()
                                ->title('Extraction OCR réussie')
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('OCR Live Error: '.$e->getMessage());
                        }
                    }),
                DatePicker::make('date')
                    ->label('Date')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->required(),
                TextInput::make('amount_ttc')
                    ->label('Montant TTC')
                    ->required()
                    ->numeric(),
                TextInput::make('amount_ht')
                    ->label('Montant HT')
                    ->numeric(),
                TextInput::make('vat_amount')
                    ->label('Montant VAT')
                    ->numeric(),
                TextInput::make('merchant')
                    ->label('Marchand'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('category')
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Catégorie'),
                TextColumn::make('amount_ttc')
                    ->label('Montant TTC')
                    ->money('EUR'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
