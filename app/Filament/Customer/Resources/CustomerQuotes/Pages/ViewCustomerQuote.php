<?php

namespace App\Filament\Customer\Resources\CustomerQuotes\Pages;

use App\Enums\Commerce\QuoteStatus;
use App\Filament\Customer\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\QuoteService;
use App\Services\Core\SignatureService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerQuote extends ViewRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected static ?string $breadcrumb = 'Devis';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('acceptedQuote')
                ->label('Accepter')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Model $record) => $record->status === QuoteStatus::SENT)
                ->modalHeading('Acceptation du devis '.$this->record->reference)
                ->modalDescription("L'acceptation du présent devis vos acceptation également des Conditions générales de ventes de l'entreprise.")
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('first_name')
                                ->label('Nom')
                                ->required(),

                            TextInput::make('last_name')
                                ->label('Prénom')
                                ->required(),
                        ]),
                    SignaturePad::make('signature')
                        ->label('Signature'),

                    Checkbox::make('accepte_tos')
                        ->label("J'accepte les conditions générales de ventes de l'entreprise"),

                    Checkbox::make('accepte_rgpd')
                        ->label("J'acepte que mes données relatives à ce devis soit réutiliser dans le cadre de l'exécution de la commande qui va en découler et plus généralement dans toutes communications qui en découlera."),
                ])
                ->action(function (array $data, Model $record) {
                    // dd($data, $record);
                    try {
                        app(SignatureService::class)->sign(
                            model: $record,
                            signatureData: $data['signature'],
                            additionalMetadata: $data,
                        );

                        app(QuoteService::class)->acceptQuote($record, $record->user);

                        Notification::make()
                            ->success()
                            ->title("Devis {$record->reference} signée avec succès")
                            ->send();
                    } catch (\Exception $exception) {
                        Log::error($exception->getMessage());

                        Notification::make()
                            ->danger()
                            ->title('Erreur lors de la signature du devis.')
                            ->body("L'administrateur à été prévenue du problème.")
                            ->send();
                    }
                })
                ->icon(Phosphor::Check),

            Action::make('refusedQuote')
                ->label('Refuser')
                ->color('danger')
                ->visible(fn (Model $record) => $record->status === QuoteStatus::SENT)
                ->action(function (Model $record) {
                    $record->update(['status' => QuoteStatus::REJECTED]);
                })
                ->icon(Phosphor::X),

            ActionGroup::make([
                Action::make('printQuote')
                    ->label('Imprimer le PDF')
                    ->action(fn (Model $record, CommerceDocumentationService $service) => $service->download($service->generateQuotePdf($record)))
                    ->icon(Phosphor::Printer),
            ]),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Devis N°'.$this->record->reference;
    }
}
