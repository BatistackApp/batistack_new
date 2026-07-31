<?php

namespace App\Filament\RH\Resources\ExpenseReports\RelationManagers;

use App\Enums\RH\ExpenseItemStatus;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Log;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

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
                Select::make('payment_method')
                    ->label('Moyen de paiement')
                    ->options(\App\Enums\RH\ExpensePaymentMethod::class)
                    ->required()
                    ->default(\App\Enums\RH\ExpensePaymentMethod::PERSONAL_CARD->value),
                \Filament\Forms\Components\SpatieMediaLibraryFileUpload::make('receipts')
                    ->label('Preuve (Ticket)')
                    ->collection('receipts')
                    ->image()
                    ->columnSpanFull()
                    ->live(onBlur: false)
                    ->afterStateUpdated(function ($state, Set $set) {
                        if (empty($state)) return;

                        $file = is_array($state) ? array_values($state)[0] ?? null : $state;
                        if (!$file || !method_exists($file, 'getRealPath')) return;

                        $path = $file->getRealPath();

                        try {
                            $ocrService = app(\App\Services\RH\GoogleCloudVisionOcrService::class);
                            $extractedData = $ocrService->extractData($path);

                            if (!empty($extractedData['amount_ttc'])) {
                                $set('amount_ttc', $extractedData['amount_ttc']);
                            }
                            if (!empty($extractedData['amount_ht'])) {
                                $set('amount_ht', $extractedData['amount_ht']);
                            }
                            if (!empty($extractedData['vat_amount'])) {
                                $set('vat_amount', $extractedData['vat_amount']);
                            }
                            if (!empty($extractedData['date'])) {
                                $set('date', \Carbon\Carbon::parse($extractedData['date'])->format('Y-m-d'));
                            }
                            if (!empty($extractedData['merchant'])) {
                                $set('merchant', $extractedData['merchant']);
                            }
                            if (!empty($extractedData['category'])) {
                                $set('category', $extractedData['category']);
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Extraction OCR réussie')
                                ->send();
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('OCR Live Error: ' . $e->getMessage());
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
                Select::make('status')
                    ->label('Status')
                    ->options(ExpenseItemStatus::class)
                    ->required(),
                TextInput::make('rejection_reason')
                    ->label('Raison du rejet'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('chantier.name')
                            ->label('Chantier')
                            ->placeholder('-'),
                        TextEntry::make('category')->label('Categorie'),
                        TextEntry::make('vehicle.license_plate')
                            ->label('Véhicule')
                            ->visible(fn ($record) => in_array($record->category, ['Carburant', 'Péage', 'Parking']))
                            ->placeholder('-'),
                        TextEntry::make('date')
                            ->label('Date')
                            ->date(),
                        TextEntry::make('amount_ttc')
                            ->label('Montant TTC')
                            ->numeric(),
                        TextEntry::make('amount_ht')
                            ->label('Montant HT')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('vat_amount')
                            ->label('Montant VAT')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('merchant')
                            ->label('Marchand')
                            ->placeholder('-'),
                        TextEntry::make('payment_method')
                            ->label('Moyen de paiement')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('rejection_reason')
                            ->label('Raison du rejet')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                        \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('receipts')
                            ->label('Preuve (Ticket)')
                            ->collection('receipts')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('category')
            ->columns([
                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Categorie')
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount_ttc')
                    ->label('Montant TTC')
                    ->money('eur', true, 'fr')
                    ->sortable(),
                TextColumn::make('amount_ht')
                    ->label('Montant HT')
                    ->money('eur', true, 'fr')
                    ->sortable(),
                TextColumn::make('vat_amount')
                    ->label('Montant VAT')
                    ->money('eur', true, 'fr')
                    ->sortable(),
                TextColumn::make('merchant')
                    ->label('Marchand')
                    ->searchable(),
                TextColumn::make('payment_method')
                    ->label('Paiement')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('scanReceipt')
                    ->label('Scanner un ticket')
                    ->icon('heroicon-o-camera')
                    ->schema([
                        FileUpload::make('receipt_image')
                            ->label('Photo du ticket')
                            ->image()
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $path = storage_path('app/public/' . $data['receipt_image']);
                        $ocrService = new \App\Services\RH\GoogleCloudVisionOcrService();
                        $extractedData = $ocrService->extractData($path);

                        $item = $livewire->getOwnerRecord()->items()->create([
                            'category' => 'Autre',
                            'date' => $extractedData['date'] ?? now(),
                            'amount_ttc' => $extractedData['amount_ttc'] ?? 0,
                            'amount_ht' => $extractedData['amount_ht'],
                            'vat_amount' => $extractedData['vat_amount'],
                            'merchant' => $extractedData['merchant'],
                            'status' => 'pending',
                            'payment_method' => \App\Enums\RH\ExpensePaymentMethod::PERSONAL_CARD->value,
                        ]);

                        try {
                            $item->addMedia($path)->toMediaCollection('receipts');
                        } catch (\Exception $e) {
                            Log::error('Media attach error: ' . $e->getMessage());
                        }

                        $livewire->mountTableAction('edit', $item);
                    }),
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn(\App\Models\RH\ExpenseItem $record) => $record->update(['status' => \App\Enums\RH\ExpenseItemStatus::APPROVED]))
                    ->visible(fn(\App\Models\RH\ExpenseItem $record) => $record->status === \App\Enums\RH\ExpenseItemStatus::PENDING),
                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->schema([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Motif du rejet')
                            ->required(),
                    ])
                    ->action(function (\App\Models\RH\ExpenseItem $record, array $data) {
                        $record->update([
                            'status' => \App\Enums\RH\ExpenseItemStatus::REJECTED,
                            'rejection_reason' => $data['reason'],
                        ]);
                    })
                    ->visible(fn(\App\Models\RH\ExpenseItem $record) => $record->status === \App\Enums\RH\ExpenseItemStatus::PENDING),
                ViewAction::make(),
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
