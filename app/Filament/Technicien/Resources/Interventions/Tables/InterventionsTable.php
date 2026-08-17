<?php

namespace App\Filament\Technicien\Resources\Interventions\Tables;

use App\Enums\Core\SignatureType;
use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\InterventionReportTemplate;
use App\Services\Core\SignatureService;
use App\Services\Interventions\InterventionPdfService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class InterventionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('thirdParty.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),

                TextColumn::make('scheduled_at')
                    ->label('Date planifiée')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label('Date de clôture')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(InterventionStatus::class),
                SelectFilter::make('type')->label('Type')
                    ->label('Type')
                    ->options(InterventionType::class),
                SelectFilter::make('third_party_id')
                    ->label('Client')
                    ->relationship('thirdParty', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('sign')
                    ->label('Faire Signer')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn (Intervention $record) => $record->status === InterventionStatus::TERMINEE)
                    ->form([
                        TextInput::make('signer_name')
                            ->label('Nom du signataire (Client)')
                            ->required(),
                        SignaturePad::make('signature')
                            ->label('Signature')
                            ->required(),
                    ])
                    ->action(function (Intervention $record, array $data, SignatureService $signatureService) {
                        $signatureService->sign(
                            model: $record,
                            signatureData: $data['signature'],
                            type: SignatureType::AUTOGRAPH,
                            additionalMetadata: [
                                'signer_name' => $data['signer_name'],
                                'role' => 'client',
                            ]
                        );

                        Notification::make()
                            ->title('Intervention signée et scellée avec succès !')
                            ->success()
                            ->send();
                    }),
                Action::make('download_pdf')
                    ->label('Télécharger le Bon')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Intervention $record, InterventionPdfService $pdfService) {
                        $path = $pdfService->generatePdf($record);

                        return response()->download($path);
                    }),
                Action::make('fill_report')
                    ->label('Remplir le rapport')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->visible(fn (Intervention $record) => InterventionReportTemplate::query()
                        ->where('intervention_type', $record->type)
                        ->where('is_active', true)
                        ->exists())
                    ->url(fn (Intervention $record) => '/technicien/fill-intervention-report?intervention_id='.$record->id),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
