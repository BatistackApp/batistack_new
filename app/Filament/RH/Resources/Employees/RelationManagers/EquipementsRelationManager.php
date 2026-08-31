<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Enums\RH\EquipementStatus;
use App\Enums\RH\EquipementType;
use App\Models\RH\Equipement;
use App\Services\Immobilisation\ImmobilisationDocumentService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Marcelorodrigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EquipementsRelationManager extends RelationManager
{
    protected static string $relationship = 'equipements';

    protected static ?string $title = 'Matériel & EPI Confiés';

    protected static string|null|\BackedEnum $icon = Phosphor::Handbag;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du matériel')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('type')->label('Type')
                            ->label('Catégorie')
                            ->options(EquipementType::class)
                            ->required()
                            ->native(false),
                        TextInput::make('label')
                            ->label('Désignation')
                            ->required()
                            ->placeholder('ex: Harnais de sécurité antichute'),
                        TextInput::make('brand')
                            ->label('Marque'),
                        TextInput::make('serial_number')
                            ->label('Numéro de série / Immatriculation')
                            ->unique(ignoreRecord: true),
                        BarcodeInput::make('barcode')
                            ->label('Code-barres / Tag')
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Scanner le code...'),
                        Select::make('item_id')
                            ->label('Article lié (Logistique)')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->nullable(),
                        Select::make('status')->label('Statut')
                            ->label('Statut')
                            ->options(EquipementStatus::class)
                            ->default(EquipementStatus::AVAILABLE)
                            ->required(),
                        TextInput::make('daily_cost')
                            ->label('Coût journalier d\'immobilisation (€)')
                            ->numeric()
                            ->default(0)
                            ->prefix('€'),
                        DatePicker::make('assigned_at')
                            ->label('Date de remise')
                            ->default(now())
                            ->native(false),
                        DatePicker::make('expires_at')
                            ->label('Échéance / Péremption')
                            ->helperText('Obligatoire pour les EPI (Casques, Harnais, etc.)')
                            ->native(false),
                        Textarea::make('notes')->label('Notes')
                            ->label('Observations (état au remise, etc.)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                IconColumn::make('type')->label('Type')
                    ->label('')
                    ->icon(fn ($state) => $state->getIcon()),
                TextColumn::make('label')
                    ->label('Désignation')
                    ->searchable()
                    ->description(fn ($record) => $record->brand.($record->serial_number ? " | S/N: {$record->serial_number}" : '')),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('assigned_at')
                    ->label('Remis le')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Péremption / Renouvellement')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'gray')
                    ->weight(fn ($state) => $state && $state->isPast() ? 'bold' : 'normal'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Confier un matériel'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('print_qr')
                    ->label('Imprimer QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('gray')
                    ->action(function (Equipement $record) {
                        $service = new ImmobilisationDocumentService;
                        $path = $service->generateQrLabel($record);

                        return $service->download($path);
                    }),
                Action::make('generate_scan_qr')
                    ->label('QR Scan Terrain')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('primary')
                    ->action(function (Equipement $record) {
                        $url = url('/terrain/scan-materiel?token='.$record->qr_token);
                        $options = new QROptions([
                            'version' => 5,
                            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                            'eccLevel' => QRCode::ECC_L,
                            'scale' => 6,
                            'imageBase64' => true,
                        ]);
                        $qrData = (new QRCode($options))->render($url);

                        $decoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $qrData));

                        return response($decoded, 200, [
                            'Content-Type' => 'image/png',
                            'Content-Disposition' => 'inline; filename="qr-scan-'.$record->qr_token.'.png"',
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
