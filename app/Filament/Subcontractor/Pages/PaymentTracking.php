<?php

namespace App\Filament\Subcontractor\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Tiers\ThirdParty;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

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
        $this->tableData = [
            'total_facture' => $this->getTotalFacture(),
            'retenues_garantie' => $this->getRetenuesGarantie(),
            'total_paye' => $this->getTotalPaye(),
        ];
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
                            ->when($data['created_before'], fn ($q, $date) => $q->where('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('download_invoice')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (SubcontractorSituation $record) => $record->getFirstMediaUrl('invoice_document'))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                //
            ])
            ->paginated([10, 25, 50]);
    }

    private function getSubcontractor(): ?ThirdParty
    {
        $user = auth()->user();

        if (! $user || ! $user->contact || ! $user->contact->thirdParty) {
            return null;
        }

        return $user->contact->thirdParty;
    }

    private function getTotalFacture(): float
    {
        $thirdParty = $this->getSubcontractor();

        if (! $thirdParty) {
            return 0;
        }

        return (float) SubcontractorSituation::where('subcontractor_id', $thirdParty->id)
            ->sum('total_ht');
    }

    private function getRetenuesGarantie(): float
    {
        $thirdParty = $this->getSubcontractor();

        if (! $thirdParty) {
            return 0;
        }

        return (float) SubcontractorSituation::where('subcontractor_id', $thirdParty->id)
            ->sum('retenue_garantie_amount');
    }

    private function getTotalPaye(): float
    {
        $thirdParty = $this->getSubcontractor();

        if (! $thirdParty) {
            return 0;
        }

        return (float) SubcontractorSituation::where('subcontractor_id', $thirdParty->id)
            ->where('status', InvoiceStatus::PAID)
            ->sum('total_ht');
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
