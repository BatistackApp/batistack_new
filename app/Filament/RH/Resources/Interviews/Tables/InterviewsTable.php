<?php

namespace App\Filament\RH\Resources\Interviews\Tables;

use App\Enums\RH\InterviewStatus;
use App\Enums\RH\InterviewType;
use App\Models\RH\Interview;
use App\Services\RH\InterviewPdfService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InterviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.last_name')
                    ->label('Employé')
                    ->formatStateUsing(fn ($record) => "{$record->employee->first_name} {$record->employee->last_name}")
                    ->searchable()
                    ->sortable(),
                TextColumn::make('manager.name')
                    ->label('Manager')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Date'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(InterviewStatus::class),
                SelectFilter::make('type')
                    ->options(InterviewType::class),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('generate_pdf')
                    ->label('Générer PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Interview $record, InterviewPdfService $pdfService) {
                        try {
                            $filePath = $pdfService->generatePdf($record);

                            Notification::make()
                                ->title('PDF généré avec succès')
                                ->success()
                                ->send();

                            return response()->download($filePath);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur lors de la génération')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Générer le compte-rendu PDF')
                    ->modalDescription('Êtes-vous sûr de vouloir compiler la grille et générer le document officiel ?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
