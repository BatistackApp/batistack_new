<?php

namespace App\Filament\Resources\GeneratedDocuments\Tables;

use App\Models\Core\GeneratedDocument;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TerminatorFilter;
use Filament\Tables\Table;

class GeneratedDocumentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module')
                    ->label('Module')
                    ->badge()
                    ->color(fn (GeneratedDocument $record): string => $record->module_color)
                    ->formatStateUsing(fn (GeneratedDocument $record): string => $record->module_label)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('file_name')
                    ->label('Nom du fichier')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('entity_type')
                    ->label('Entité liée')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->searchable(),

                TextColumn::make('file_size')
                    ->label('Taille')
                    ->formatStateUsing(fn (?GeneratedDocument $record): string => $record->formatted_size)
                    ->sortable(),

                TextColumn::make('generated_at')
                    ->label('Généré le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('generatedBy.name')
                    ->label('Généré par')
                    ->formatStateUsing(fn (?string $state): string => $state ?? 'Système')
                    ->sortable(),
            ])
            ->defaultSort('generated_at', 'desc')
            ->filters([
                TerminatorFilter::make()
                    ->autoOpenAbove(),
                SelectFilter::make('module')
                    ->label('Module')
                    ->options([
                        'commerce' => 'Commerce',
                        'rh' => 'Ressources Humaines',
                        'chantiers' => 'Chantiers',
                        'tiers' => 'Tiers',
                        'gpao' => 'GPAO',
                        'flottes' => 'Flottes',
                        'immobilisations' => 'Immobilisations',
                        'interventions' => 'Interventions',
                        'articles' => 'Articles',
                    ])
                    ->multiple()
                    ->preload(),
                SelectFilter::make('type')
                    ->label('Type de document')
                    ->options([
                        'devis' => 'Devis',
                        'facture' => 'Facture',
                        'bon_de_commande' => 'Bon de commande',
                        'bon_de_livraison' => 'Bon de livraison',
                        'situation' => 'Situation',
                        'contrat' => 'Contrat',
                        'fiche_salarie' => 'Fiche salarié',
                        'timesheet' => 'Timesheet',
                        'attestation_salaire' => 'Attestation salaire',
                        'ordre_de_service' => 'Ordre de service',
                        'ppsps' => 'PPSPS',
                        'journal' => 'Journal',
                        'bilan' => 'Bilan',
                        'fiche_tiers' => 'Fiche tiers',
                        'ordre_de_fabrication' => 'Ordre de fabrication',
                        'fiche_vehicule' => 'Fiche véhicule',
                        'mise_a_disposition' => 'Mise à disposition',
                        'fiche_immobilisation' => 'Fiche immobilisation',
                        'etat_dotations' => 'État des dotations',
                        'contrat_maintenance' => 'Contrat maintenance',
                        'etiquettes' => 'Étiquettes',
                    ])
                    ->multiple()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Voir')
                    ->icon(Phosphor::Eye),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
