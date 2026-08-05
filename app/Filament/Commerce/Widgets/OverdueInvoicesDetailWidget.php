<?php

namespace App\Filament\Commerce\Widgets;

use App\Models\Commerce\CustomerInvoice;
use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use Illuminate\Support\Str;

class OverdueInvoicesDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Alertes Impayés (Retards de paiement)';
    }

    protected function getDetails(): array
    {
        $invoices = CustomerInvoice::overdue()
            ->with('client')
            ->oldest('due_date')
            ->limit(5)
            ->get();
            
        return $invoices->map(function (CustomerInvoice $invoice) {
            $days = $invoice->getDaysOverdue();
            $label = $invoice->client?->name ?? 'Client inconnu';
            $ref = $invoice->reference;
            
            return Detail::make("{$label} ({$ref})", "En retard de {$days} jours - Reste à payer: " . number_format($invoice->amount_remaining, 2, ',', ' ') . " €")
                ->icon('heroicon-o-exclamation-circle')
                ->url(CustomerInvoiceResource::getUrl('edit', ['record' => $invoice]))
                ->color('danger');
        })->toArray();
    }
}
