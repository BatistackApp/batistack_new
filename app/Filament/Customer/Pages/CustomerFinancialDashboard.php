<?php

namespace App\Filament\Customer\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Tiers\ThirdParty;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

class CustomerFinancialDashboard extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Tableau de bord financier';

    protected static ?string $title = 'Mon Suivi Financier';

    protected static string|null|\UnitEnum $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.customer.pages.customer-financial-dashboard';

    public array $stats = [];

    public array $statusBreakdown = [];

    public array $recentInvoices = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Vue d\'ensemble')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('stats.total_facture')
                                    ->label('Total facturé (TTC)')
                                    ->getStateUsing(fn () => number_format($this->stats['total_facture'] ?? 0, 2, ',', ' ').' €')
                                    ->size('lg'),

                                TextEntry::make('stats.total_paye')
                                    ->label('Total payé')
                                    ->getStateUsing(fn () => number_format($this->stats['total_paye'] ?? 0, 2, ',', ' ').' €')
                                    ->size('lg')
                                    ->color('success'),

                                TextEntry::make('stats.solde_du')
                                    ->label('Solde dû')
                                    ->getStateUsing(fn () => number_format($this->stats['solde_du'] ?? 0, 2, ',', ' ').' €')
                                    ->size('lg')
                                    ->color(fn () => ($this->stats['solde_du'] ?? 0) > 0 ? 'danger' : 'success'),
                            ]),

                        Section::make('Répartition par statut')
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                TextEntry::make('status_breakdown.validated')
                                    ->label('En attente')
                                    ->getStateUsing(fn () => ($this->statusBreakdown[InvoiceStatus::VALIDATED->value] ?? 0).' facture(s)')
                                    ->color('warning'),

                                TextEntry::make('status_breakdown.partially_paid')
                                    ->label('Partiellement payées')
                                    ->getStateUsing(fn () => ($this->statusBreakdown[InvoiceStatus::PARTIALLY_PAID->value] ?? 0).' facture(s)')
                                    ->color('info'),

                                TextEntry::make('status_breakdown.paid')
                                    ->label('Payées')
                                    ->getStateUsing(fn () => ($this->statusBreakdown[InvoiceStatus::PAID->value] ?? 0).' facture(s)')
                                    ->color('success'),
                            ]),
                    ]),
            ]);
    }

    private function loadData(): void
    {
        $thirdParty = $this->getThirdParty();

        if (! $thirdParty) {
            return;
        }

        $invoices = CustomerInvoice::where('client_id', $thirdParty->id)
            ->whereIn('status', collect(InvoiceStatus::cases())
                ->reject(fn (InvoiceStatus $s) => in_array($s, [InvoiceStatus::DRAFT, InvoiceStatus::CANCELED]))
                ->map(fn (InvoiceStatus $s) => $s->value)
                ->all()
            )
            ->get();

        $this->stats = [
            'total_facture' => (float) $invoices->sum('total_ttc'),
            'total_paye' => (float) $invoices->sum('total_allocated'),
            'solde_du' => max(0, (float) $invoices->sum('total_ttc') - (float) $invoices->sum('total_allocated')),
        ];

        $this->statusBreakdown = $invoices
            ->groupBy(fn ($invoice) => $invoice->status->value)
            ->map(fn ($group) => $group->count())
            ->toArray();

        $this->recentInvoices = CustomerInvoice::where('client_id', $thirdParty->id)
            ->whereIn('status', collect(InvoiceStatus::cases())
                ->reject(fn (InvoiceStatus $s) => in_array($s, [InvoiceStatus::DRAFT, InvoiceStatus::CANCELED]))
                ->map(fn (InvoiceStatus $s) => $s->value)
                ->all()
            )
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($invoice) => [
                'id' => $invoice->id,
                'reference' => $invoice->reference,
                'total_ttc' => $invoice->total_ttc,
                'status' => $invoice->status->getLabel(),
                'status_color' => $invoice->status->getColor(),
                'due_date' => $invoice->due_date?->format('d/m/Y'),
                'is_overdue' => $invoice->is_overdue,
            ])
            ->toArray();
    }

    private function getThirdParty(): ?ThirdParty
    {
        $user = auth()->user();

        if (! $user || ! $user->contact || ! $user->contact->thirdParty) {
            return null;
        }

        return $user->contact->thirdParty;
    }
}
