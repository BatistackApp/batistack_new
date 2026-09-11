<?php

namespace App\Filament\Laser\Resources\LaserInvoices\Pages;

use App\Enums\Laser\InvoiceStatus;
use App\Filament\Laser\Resources\LaserInvoices\LaserInvoiceResource;
use App\Services\Laser\LaserInvoiceService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewLaserInvoice extends ViewRecord
{
    protected static string $resource = LaserInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('legalize')
                ->label('Légaliser')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn ($record) => $record->status === InvoiceStatus::DRAFT)
                ->requiresConfirmation()
                ->modalHeading('Légalisation de la facture')
                ->modalDescription('La légalisation appliquera la chaîne de hash NF525. Cette action est irréversible.')
                ->action(function ($record) {
                    app(LaserInvoiceService::class)->legalizeInvoice($record);

                    Notification::make()
                        ->title('Facture légalisée')
                        ->success()
                        ->send();
                }),

            Actions\PrintAction::make()
                ->label('Imprimer PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => Storage::url('documents/laser/invoices/facture_'.$record->reference.'.pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
