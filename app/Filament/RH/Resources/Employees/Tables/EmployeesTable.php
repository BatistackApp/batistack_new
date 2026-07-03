<?php

namespace App\Filament\RH\Resources\Employees\Tables;

use App\Models\RH\Employee;
use App\Services\RH\RHDocumentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('')
                    ->collection('avatar')
                    ->circular(),

                TextColumn::make('registration_number')
                    ->label('Matricule')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('full_name')
                    ->label('Nom Complet')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name']),

                TextColumn::make('currentContract.job_title')
                    ->label('Poste Actuel')
                    ->placeholder('Aucun contrat actif')
                    ->description(fn (Employee $record) => $record->currentContract?->type?->getLabel()),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('Actif')
                    ->onColor('success')
                    ->offColor('danger'),

                IconColumn::make('onboarding_completed')
                    ->label('Onboarding')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($state) => $state ? 'Dossier complet' : 'En attente'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Filtrer par statut')
                    ->placeholder('Tous les salariés')
                    ->trueLabel('Salariés actifs')
                    ->falseLabel('Salariés sortis'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('onboarding_link')
                        ->label('Lien Onboarding')
                        ->icon('heroicon-o-link')
                        ->color('gray')
                        ->action(function (Employee $record) {
                            // URL generation using a simple route copy
                        })
                        ->extraAttributes(function (Employee $record) {
                            return [
                                'x-on:click' => "window.navigator.clipboard.writeText('".route('public.onboarding', $record->uuid)."'); \$tooltip('Lien copié !')",
                            ];
                        }),
                    Action::make('proforma')
                        ->label('Paie Pro Forma')
                        ->icon('heroicon-o-document-currency-euro')
                        ->schema([
                            Select::make('month')
                                ->label('Mois')
                                ->options([
                                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                                    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                                    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                                ])
                                ->default(now()->subMonth()->month)
                                ->required(),
                            Select::make('year')
                                ->label('Année')
                                ->options(array_combine(range(now()->year - 2, now()->year), range(now()->year - 2, now()->year)))
                                ->default(now()->year)
                                ->required(),
                        ])
                        ->action(function (Employee $record, array $data, RHDocumentService $service) {
                            $path = $service->generateProFormaPayslip($record, $data['month'], $data['year']);

                            return Storage::disk('public')->download($path);
                        }),
                    Action::make('download_affiliation')
                        ->label('Bulletin Affiliation PRO BTP')
                        ->icon(Phosphor::FilePdf)
                        ->color('info')
                        ->action(function (Employee $record) {
                            // Cherche le document généré lors de l'onboarding
                            $media = $record->getMedia('rh_documents')->filter(function ($item) {
                                return str_contains($item->file_name, 'affiliation_probtp');
                            })->last();

                            if ($media) {
                                return response()->download($media->getPath(), $media->file_name);
                            }

                            // S'il n'existe pas, on le génère à la volée (ex: salariés importés ou ancien onboarding)
                            try {
                                $pdfRelativePath = app(RHDocumentService::class)->generateAffiliationMutuelle($record);
                                $pdfAbsolutePath = Storage::disk('public')->path($pdfRelativePath);
                                $media = $record->addMedia($pdfAbsolutePath)->toMediaCollection('rh_documents');

                                return response()->download($media->getPath(), $media->file_name);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur lors de la génération')
                                    ->danger()
                                    ->send();
                            }
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
