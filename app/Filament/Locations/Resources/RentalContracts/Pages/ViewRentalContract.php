<?php

namespace App\Filament\Locations\Resources\RentalContracts\Pages;

use App\Enums\Locations\RentalStatus;
use App\Filament\Locations\Resources\RentalContracts\RentalContractResource;
use App\Services\Locations\RentalBillingService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRentalContract extends ViewRecord
{
    protected static string $resource = RentalContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('generate_invoice')
                ->label('Générer la facture')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Générer une facture brouillon')
                ->modalDescription('Cette action va générer une facture fournisseur (DRAFT) basée sur les conditions du contrat de location actuel.')
                ->action(function (RentalBillingService $billingService) {
                    try {
                        $invoice = $billingService->generateDraftInvoice($this->record);
                        
                        Notification::make()
                            ->title('Facture générée')
                            ->body("La facture brouillon {$invoice->reference} a été créée avec succès.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body("Impossible de générer la facture: {$e->getMessage()}")
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('terminate_rental')
                ->label('Terminer la location')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === RentalStatus::ACTIVE)
                ->modalHeading('Terminer la location')
                ->modalDescription('Souhaitez-vous évaluer le fournisseur pour cette location ? (Optionnel)')
                ->schema([
                    \Filament\Forms\Components\Radio::make('supplier_score')
                        ->label('Note du fournisseur')
                        ->options([
                            1 => '1 ⭐ - Très insatisfaisant',
                            2 => '2 ⭐ - Insatisfaisant',
                            3 => '3 ⭐ - Correct',
                            4 => '4 ⭐ - Satisfaisant',
                            5 => '5 ⭐ - Excellent',
                        ])
                        ->inline()
                        ->nullable(),
                    \Filament\Forms\Components\Textarea::make('supplier_feedback')
                        ->label('Commentaire / État du matériel')
                        ->rows(3)
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => RentalStatus::TERMINATED,
                        'end_date' => now(),
                        'supplier_score' => $data['supplier_score'] ?? null,
                        'supplier_feedback' => $data['supplier_feedback'] ?? null,
                    ]);
                    
                    Notification::make()
                        ->title('Location terminée')
                        ->success()
                        ->send();
                }),
        ];
    }
}
