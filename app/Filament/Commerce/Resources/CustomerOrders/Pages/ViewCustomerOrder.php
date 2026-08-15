<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Pages;

use App\Enums\Commerce\QuoteStatus;
use App\Filament\Actions\GenerateDocument;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\CancelAction;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\ConfirmedAction;
use App\Filament\Commerce\Resources\CustomerOrders\Actions\PrinterAction;
use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use App\Filament\Commerce\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Services\Commerce\QuoteService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerOrder extends ViewRecord
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CancelAction::make(),
            ConfirmedAction::make(),
            PrinterAction::make()->iconButton(),
            Action::make('create_avenant')
                ->label('Créer un avenant')
                ->icon(Phosphor::Plus)
                ->color('primary')
                ->action(function (CustomerOrder $record) {
                    $quote = CustomerQuote::create([
                        'client_id' => $record->client_id,
                        'chantier_id' => $record->chantier_id,
                        'parent_order_id' => $record->id,
                        'reference' => app(QuoteService::class)->generateReferenceAvenant(),
                        'status' => QuoteStatus::DRAFT,
                        'expires_at' => now()->addDays(30),
                        'responsable_id' => auth()->id(),
                        'is_avenant' => true,
                    ]);

                    Notification::make()
                        ->title('Avenant créé')
                        ->body("Ajoutez les travaux supplémentaires, puis envoyez l'avenant au client.")
                        ->success()
                        ->send();

                    return redirect(CustomerQuoteResource::getUrl('edit', ['record' => $quote]));
                }),
            ActionGroup::make([
                GenerateDocument::make('commande_client'),
            ]),
        ];
    }
}
