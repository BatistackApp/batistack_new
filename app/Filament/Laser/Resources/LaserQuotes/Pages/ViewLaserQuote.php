<?php

namespace App\Filament\Laser\Resources\LaserQuotes\Pages;

use App\Enums\Laser\QuoteStatus;
use App\Enums\Core\SignatureType;
use App\Filament\Laser\Resources\LaserQuotes\LaserQuoteResource;
use App\Services\Laser\LaserQuoteService;
use App\Services\Core\SignatureService;
use App\Services\Laser\LaserDocumentationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;

class ViewLaserQuote extends ViewRecord
{
    use HasPageHeader;

    protected static string $resource = LaserQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->canBeEdited()),
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->canBeDeleted()),

            Actions\Action::make('sendForSignature')
                ->label('Envoyer pour signature')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn ($record) => in_array($record->status, [QuoteStatus::DRAFT, QuoteStatus::SENT], true))
                ->requiresConfirmation()
                ->action(function ($record) {
                    if (! $record->lines()->exists()) {
                        Notification::make()->danger()->title('Le devis ne contient aucune ligne')->send();
                        return;
                    }
                    $contact = $record->client?->getPrimaryContact();
                    $email = $contact?->email ?? $record->client?->email;
                    if (! $email) {
                        Notification::make()->warning()->title('Le client n’a pas d’adresse email')->send();
                        return;
                    }
                    $record->update(['status' => QuoteStatus::SENT]);
                    $path = app(LaserDocumentationService::class)->generateQuotePdf($record);
                    app(SignatureService::class)->requestSignature($record, SignatureType::AUTOGRAPH, $email, $contact ? trim("{$contact->first_name} {$contact->last_name}") : $record->client->name, $path);
                    Notification::make()->success()->title('Devis envoyé pour signature')->send();
                }),

            Actions\Action::make('convertToOrder')
                ->label('Transformer en commande')
                ->icon('heroicon-o-shopping-bag')
                ->color('success')
                ->visible(fn ($record) => ! $record->order()->exists() && $record->status === QuoteStatus::ACCEPTED)
                ->requiresConfirmation()
                ->modalHeading('Transformer ce devis en commande')
                ->modalDescription('Une commande sera créée avec les lignes du devis. Cette action est irréversible.')
                ->action(function ($record) {
                     $order = app(LaserQuoteService::class)->convertToOrder($record);

                    Notification::make()
                        ->title('Commande créée : '.$order->reference)
                        ->success()
                        ->send();

                    return $this->redirect(route('filament.laser.resources.laser-orders.view', $order));
                }),
        ];
    }
}
