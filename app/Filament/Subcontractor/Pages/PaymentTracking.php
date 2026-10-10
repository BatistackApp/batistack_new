<?php

namespace App\Filament\Subcontractor\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Tiers\ThirdParty;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class PaymentTracking extends Page implements Tables\Contracts\HasTable
{
    use InteractsWithTable;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Suivi des paiements';

    protected static ?string $title = 'Suivi des Paiements';

    protected static string|null|\UnitEnum $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.subcontractor.pages.payment-tracking';

    public ?array $tableData = [];

    public function mount(): void
    {
        $this->tableData = $this->getCustomerInvoiceStats();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SubcontractorSituation::query()
                    ->where('subcontractor_id', $this->getSubcontractor()?->id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('chantier.reference')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('retenue_garantie_amount')
                    ->label('Retenue garantie')
                    ->money('EUR')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Avancement')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => $state.' %')
                    ->color(fn (int $state): string => match (true) {
                        $state === 100 => 'success',
                        $state > 0 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => $state->getColor()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(InvoiceStatus::class)
                    ->multiple(),
                Tables\Filters\SelectFilter::make('chantier_id')
                    ->label('Chantier')
                    ->options(fn () => $this->getChantierOptions())
                    ->searchable(),
                Tables\Filters\Filter::make('date_range')
                    ->label('Période')
                    ->form([
                        DatePicker::make('created_after')
                            ->label('Après le')
                            ->placeholder('Date début'),
                        DatePicker::make('created_before')
                            ->label('Avant le')
                            ->placeholder('Date fin'),
                    ])
                    ->query(function ($query, array $data): void {
                        $query
                            ->when($data['created_after'], fn ($q, $date) => $q->where('created_at', '>=', $date))
                            ->when($data['created_before'], fn ($q, $date) => $q->where('created_at', '<=', Carbon::parse($date)->endOfDay()));
                    }),
            ])
            ->actions([
                Action::make('download_invoice')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (SubcontractorSituation $record): string => route('subcontractor.documents.invoice.download', ['situation' => $record->getKey()]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                //
            ])
            ->paginated([10, 25, 50]);
    }

    /**
     * Totaux des factures clients émises au sous-traitant connecté.
     * Les brouillons et factures annulées ne sont pas comptabilisés.
     *
     * @return array{total_facture: float, total_paye: float, reste_a_encaisser: float}
     */
    public function getCustomerInvoiceStats(): array
    {
        $subcontractor = $this->getSubcontractor();

        if (! $subcontractor || $subcontractor->type !== ThirdPartyType::SUBCONTRACTOR) {
            return ['total_facture' => 0.0, 'total_paye' => 0.0, 'reste_a_encaisser' => 0.0];
        }

        $invoices = CustomerInvoice::query()
            ->where('client_id', $subcontractor->getKey())
            ->whereNotIn('status', [InvoiceStatus::DRAFT->value, InvoiceStatus::CANCELED->value])
            ->withSum('allocations', 'allocated_amount')
            ->get();

        $total = (float) $invoices->sum(fn (CustomerInvoice $invoice): float => (float) $invoice->total_ttc);
        $paid = (float) $invoices->sum(fn (CustomerInvoice $invoice): float => (float) ($invoice->allocations_sum_allocated_amount ?? 0));

        return [
            'total_facture' => $total,
            'total_paye' => $paid,
            'reste_a_encaisser' => max(0, $total - $paid),
        ];
    }

    public function getCustomerInvoices(): Collection
    {
        $subcontractor = $this->getSubcontractor();

        if (! $subcontractor || $subcontractor->type !== ThirdPartyType::SUBCONTRACTOR) {
            return collect();
        }

        return CustomerInvoice::query()
            ->where('client_id', $subcontractor->getKey())
            ->withSum('allocations', 'allocated_amount')
            ->orderByDesc('created_at')
            ->get();
    }

    private function getSubcontractor(): ?ThirdParty
    {
        $user = auth()->user();

        if (! $user || ! $user->contact || ! $user->contact->thirdParty) {
            return null;
        }

        return $user->contact->thirdParty;
    }

    private function getChantierOptions(): array
    {
        $thirdParty = $this->getSubcontractor();

        if (! $thirdParty) {
            return [];
        }

        return $thirdParty->chantiers()
            ->pluck('reference', 'id')
            ->toArray();
    }
}
