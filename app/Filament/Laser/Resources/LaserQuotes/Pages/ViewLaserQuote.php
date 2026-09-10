<?php

namespace App\Filament\Laser\Resources\LaserQuotes\Pages;

use App\Enums\Laser\QuoteStatus;
use App\Filament\Laser\Resources\LaserQuotes\LaserQuoteResource;
use App\Services\Laser\LaserQuoteService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLaserQuote extends ViewRecord
{
    protected static string $resource = LaserQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('convertToOrder')
                ->label('Transformer en commande')
                ->icon('heroicon-o-shopping-bag')
                ->color('success')
                ->visible(fn ($record) => in_array($record->status, [QuoteStatus::DRAFT, QuoteStatus::SENT, QuoteStatus::ACCEPTED]))
                ->requiresConfirmation()
                ->modalHeading('Transformer ce devis en commande')
                ->modalDescription('Une commande sera créée avec les lignes du devis. Cette action est irréversible.')
                ->action(function ($record) {
                    $order = app(LaserQuoteService::class)->acceptQuote($record);

                    Notification::make()
                        ->title('Commande créée : '.$order->reference)
                        ->success()
                        ->send();

                    return $this->redirect(route('filament.laser.resources.laser-orders.view', $order));
                }),
        ];
    }
}
