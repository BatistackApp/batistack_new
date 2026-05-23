<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Pages;

use App\Enums\Commerce\QuoteStatus;
use App\Filament\Commerce\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Models\Commerce\CustomerQuote;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
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
        ];
    }
}
