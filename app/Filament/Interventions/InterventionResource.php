<?php

namespace App\Filament\Interventions;

use App\Filament\Interventions\Pages\CreateIntervention;
use App\Filament\Interventions\Pages\EditIntervention;
use App\Filament\Interventions\Pages\ListInterventions;
use App\Filament\Interventions\Schemas\InterventionForm;
use App\Filament\Interventions\Tables\InterventionsTable;
use App\Models\Interventions\Intervention;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InterventionResource extends Resource
{
    protected static ?string $model = Intervention::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InterventionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return \App\Filament\Interventions\Schemas\InterventionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InterventionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInterventions::route('/'),
            'create' => CreateIntervention::route('/create'),
            'view' => \App\Filament\Interventions\Pages\ViewIntervention::route('/{record}'),
            'edit' => EditIntervention::route('/{record}/edit'),
        ];
    }

    public static function getSharedActions(): array
    {
        return [
            \Filament\Actions\Action::make('sign')
                ->label('Faire Signer')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->visible(fn (\App\Models\Interventions\Intervention $record) => $record->status === \App\Enums\Interventions\InterventionStatus::TERMINEE)
                ->form([
                    \Filament\Forms\Components\TextInput::make('signer_name')
                        ->label('Nom du signataire (Client)')
                        ->required(),
                    \Saade\FilamentAutograph\Forms\Components\SignaturePad::make('signature')
                        ->label('Signature')
                        ->required(),
                ])
                ->action(function (\App\Models\Interventions\Intervention $record, array $data, \App\Services\Core\SignatureService $signatureService) {
                    $signatureService->sign(
                        model: $record,
                        signatureData: $data['signature'],
                        type: \App\Enums\Core\SignatureType::AUTOGRAPH,
                        additionalMetadata: [
                            'signer_name' => $data['signer_name'],
                            'role' => 'client',
                        ]
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('Intervention signée et scellée avec succès !')
                        ->success()
                        ->send();
                }),
            \Filament\Actions\Action::make('download_pdf')
                ->label('Télécharger le Bon')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (\App\Models\Interventions\Intervention $record, \App\Services\Interventions\InterventionPdfService $pdfService) {
                    $path = $pdfService->generatePdf($record);

                    return response()->download($path);
                }),
            \Filament\Actions\Action::make('create_invoice')
                ->label('Générer Facture')
                ->icon('heroicon-o-document-currency-euro')
                ->color('warning')
                ->visible(fn (\App\Models\Interventions\Intervention $record) => $record->status === \App\Enums\Interventions\InterventionStatus::TERMINEE)
                ->requiresConfirmation()
                ->action(function (\App\Models\Interventions\Intervention $record, \App\Services\Interventions\InterventionBillingService $billingService) {
                    try {
                        $invoice = $billingService->generateInvoice($record);
                        if ($invoice) {
                            \Filament\Notifications\Notification::make()
                                ->title('Facture générée avec succès !')
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erreur lors de la facturation')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
