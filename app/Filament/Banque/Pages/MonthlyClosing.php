<?php

namespace App\Filament\Banque\Pages;

use App\Filament\Banque\Widgets\HighValueAnomaliesWidget;
use App\Filament\Banque\Widgets\ManualPaidCustomerInvoicesWidget;
use App\Filament\Banque\Widgets\ManualPaidSupplierInvoicesWidget;
use App\Filament\Banque\Widgets\UncategorizedTransactionsWidget;
use Filament\Pages\Page;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class MonthlyClosing extends Page
{
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Supervision';
    }

    public static function getNavigationLabel(): string
    {
        return 'Clôture Mensuelle';
    }

    public function getTitle(): string
    {
        return 'Anomalies de Clôture Mensuelle';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UncategorizedTransactionsWidget::class,
            HighValueAnomaliesWidget::class,
            ManualPaidCustomerInvoicesWidget::class,
            ManualPaidSupplierInvoicesWidget::class,
        ];
    }
}
