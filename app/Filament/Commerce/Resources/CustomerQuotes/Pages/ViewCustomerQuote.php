<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Pages;

use App\Enums\Commerce\QuoteStatus;
use App\Filament\Commerce\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Models\Commerce\CustomerQuote;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\QuoteService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerQuote extends ViewRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendingQuote')
                ->label('Envoyer le devis')
                ->icon(Phosphor::Envelope)
                ->color('primary')
                ->visible(fn (CustomerQuote $record) => $record->status === QuoteStatus::DRAFT || $record->status === QuoteStatus::SENT)
                ->requiresConfirmation()
                ->modalHeading('Envoyer le devis')
                ->modalDescription('Envoyer le devis au client va valider automatiquement le devis, vous ne pourrez plus le modifier, Etes-vous sur ?')
                ->modalIconColor('warning')
                ->color('warning')
                ->action(function (CustomerQuote $record) {
                    if ($record->items()->count() === 0) {
                        Notification::make()
                            ->danger()
                            ->title('Le devis ne contient aucune ligne')
                            ->send();

                        return;
                    }

                    $record->update(['status' => QuoteStatus::SENT]);

                    Notification::make()
                        ->success()
                        ->title('Devis envoyer au client')
                        ->send();
                }),

            MediaAction::make()
                ->label('Imprimer PDF')
                ->icon(Phosphor::Printer)
                ->mediaType(MediaAction::TYPE_PDF)
                ->modalWidth(Width::Container)
                ->media(function (Model $record) {
                    $path = 'commerce/quotes/devis_'.$record->reference.'.pdf';
                    if (! Storage::disk('public')->exists('documents/'.$path)) {
                        app(CommerceDocumentationService::class)->generateQuotePdf($record);
                    }
                    return Storage::url('documents/'.$path);
                }),

            ActionGroup::make([
                Action::make('manualAccept')
                    ->visible(fn (Model $record) => $record->status === QuoteStatus::SENT)
                    ->label('Accepter')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Accepter le devis')
                    ->modalDescription('Accepter le devis va valider automatiquement le devis, vous ne pourrez plus le modifier, Etes-vous sur ?')
                    ->action(function (Model $record) {
                        app(QuoteService::class)->acceptQuote($record, auth()->user());
                    }),

                Action::make('manualRefused')
                    ->visible(fn (Model $record) => $record->status === QuoteStatus::SENT)
                    ->label('Refuser')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Refuser le devis')
                    ->modalDescription('Refuser le devis va valider automatiquement le devis, vous ne pourrez plus le modifier, Etes-vous sur ?')
                    ->action(function (Model $record) {
                        $record->update(['status' => QuoteStatus::REJECTED]);
                    }),
            ]),
        ];
    }
}
