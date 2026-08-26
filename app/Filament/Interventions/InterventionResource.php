<?php

namespace App\Filament\Interventions;

use App\Enums\Core\SignatureType;
use App\Enums\Interventions\InterventionStatus;
use App\Filament\Interventions\Pages\CreateIntervention;
use App\Filament\Interventions\Pages\EditIntervention;
use App\Filament\Interventions\Pages\ListInterventions;
use App\Filament\Interventions\Pages\ViewIntervention;
use App\Filament\Interventions\Schemas\InterventionForm;
use App\Filament\Interventions\Schemas\InterventionInfolist;
use App\Filament\Interventions\Tables\InterventionsTable;
use App\Models\Interventions\Intervention;
use App\Services\Core\SignatureService;
use App\Services\Interventions\InterventionBillingService;
use App\Services\Interventions\InterventionPdfService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

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
        return InterventionInfolist::configure($schema);
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
            'view' => ViewIntervention::route('/{record}'),
            'edit' => EditIntervention::route('/{record}/edit'),
        ];
    }

    public static function getSharedActions(): array
    {
        return [
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
            Action::make('create_invoice')
                ->label('Générer Facture')
                ->icon('heroicon-o-document-currency-euro')
                ->color('warning')
                ->visible(fn (Intervention $record) => $record->status === InterventionStatus::TERMINEE)
                ->requiresConfirmation()
                ->action(function (Intervention $record, InterventionBillingService $billingService) {
                    try {
                        $invoice = $billingService->generateInvoice($record);
                        if ($invoice) {
                            Notification::make()
                                ->title('Facture générée avec succès !')
                                ->success()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur lors de la facturation')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
