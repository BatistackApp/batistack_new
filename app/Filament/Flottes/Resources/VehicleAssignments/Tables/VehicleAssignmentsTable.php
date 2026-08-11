<?php

namespace App\Filament\Flottes\Resources\VehicleAssignments\Tables;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\ConditionReportType;
use App\Models\Flottes\VehicleAssignment;
use App\Services\Flottes\FleetDocumentService;
use App\Services\Flottes\VehicleConditionService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VehicleAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('vehicle.reference')
                    ->label('Véhicule')
                    ->description(fn (VehicleAssignment $record) => $record->vehicle->license_plate)
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('employee.full_name')
                    ->label('Conducteur responsable')
                    ->searchable(),
                TextColumn::make('chantier.name')
                    ->label('Imputation Chantier')
                    ->placeholder('Logistique générale / Dépôt')
                    ->wrap(),
                TextColumn::make('started_at')
                    ->label('Heure de départ')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('status')->label('Statut')
                    ->label('État')
                    ->badge(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    // --- ACTION DE RESTITUTION ET D'ÉTAT DES LIEUX PHOTO (Check-out) ---
                    Action::make('checkout')
                        ->label('Restituer (Check-out)')
                        ->icon(Phosphor::Key)
                        ->color('success')
                        ->visible(fn (VehicleAssignment $record) => $record->status === AssignmentStatus::ACTIVE)
                        ->modalWidth('lg')
                        ->schema([
                            Placeholder::make('info')
                                ->content('Prenez 4 photos du véhicule sous chaque angle + 1 photo de l\'odomètre pour valider le retour.'),

                            Grid::make(2)->schema([
                                TextInput::make('odometer')
                                    ->label('Kilométrage (ou heures moteur) final')
                                    ->required()
                                    ->numeric(),
                                TextInput::make('fuel_level')
                                    ->label('Niveau de carburant (%)')
                                    ->required()
                                    ->numeric()
                                    ->suffix('%'),
                            ]),

                            FileUpload::make('photo_front')
                                ->label('Photo Face Avant')
                                ->image()
                                ->required(),
                            FileUpload::make('photo_back')
                                ->label('Photo Face Arrière')
                                ->image()
                                ->required(),
                            FileUpload::make('photo_left')
                                ->label('Photo Profil Gauche')
                                ->image()
                                ->required(),
                            FileUpload::make('photo_right')
                                ->label('Photo Profil Droite')
                                ->image()
                                ->required(),
                            FileUpload::make('photo_dashboard')
                                ->label('Photo Tableau de bord (Compteur)')
                                ->image()
                                ->required(),

                            Textarea::make('comments')
                                ->label('Observations éventuelles / Chocs constatés'),

                            TextInput::make('driver_pin')
                                ->label('Code PIN de signature numérique')
                                ->password()
                                ->required()
                                ->maxLength(4)
                                ->helperText('Saisissez votre code personnel de 4 chiffres pour valider la décharge.'),
                        ])
                        ->action(function (VehicleAssignment $record, array $data) {
                            try {
                                $photos = [
                                    'front' => $data['photo_front'],
                                    'back' => $data['photo_back'],
                                    'left' => $data['photo_left'],
                                    'right' => $data['photo_right'],
                                    'dashboard' => $data['photo_dashboard'],
                                ];

                                app(VehicleConditionService::class)->submitReport(
                                    $record,
                                    ConditionReportType::CHECK_OUT,
                                    (float) $data['odometer'],
                                    (int) $data['fuel_level'],
                                    $data['driver_pin'],
                                    $photos,
                                    $data['comments'] ?? null
                                );

                                Notification::make()
                                    ->title('Véhicule restitué au dépôt')
                                    ->success()
                                    ->body('L\'état des lieux de sortie a été signé numériquement et archivé.')
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur de validation')
                                    ->danger()
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    // --- ACTION D'IMPRESSION DU CONTRAT JURIDIQUE ---
                    Action::make('printAssignment')
                        ->label('Imprimer la décharge')
                        ->icon(Phosphor::Printer)
                        ->color('info')
                        ->action(function (VehicleAssignment $record, FleetDocumentService $service) {
                            $path = $service->generateAssignmentForm($record);
                            $disk = \App\Services\Core\DocumentService::getDisk();

                            return \Illuminate\Support\Facades\Storage::disk($disk)->download($path);
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
