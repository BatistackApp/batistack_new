<?php

namespace App\Filament\Flottes\Pages;

use App\Models\Flottes\FuelTransaction;
use App\Services\Core\DocumentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RseReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|null|\UnitEnum $navigationGroup = 'Rapports';

    protected static ?string $title = 'Bilan Carbone (RSE)';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.flottes.pages.rse-report';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount()
    {
        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->endOfYear()->format('Y-m-d');

        $this->form->fill([
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('dateFrom')
                                ->label('De')
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state) => $this->dateFrom = $state),
                            DatePicker::make('dateTo')
                                ->label('À')
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state) => $this->dateTo = $state),
                        ]),
                    ]),
            ]);
    }

    protected function getTransactionsQuery()
    {
        return FuelTransaction::query()
            ->when($this->dateFrom, fn ($q) => $q->whereDate('purchased_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('purchased_at', '<=', $this->dateTo));
    }

    public function getTotalCo2(): float
    {
        return (float) $this->getTransactionsQuery()->sum('co2_emission_kg');
    }

    public function getCo2ByMonth(): array
    {
        $transactions = $this->getTransactionsQuery()
            ->selectRaw('DATE_FORMAT(purchased_at, "%Y-%m") as month, SUM(co2_emission_kg) as total_kg')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $data = [];
        foreach ($transactions as $t) {
            $data[$t->month] = $t->total_kg;
        }

        return $data;
    }

    public function getCo2ByChantier(): array
    {
        $transactions = $this->getTransactionsQuery()
            ->whereNotNull('chantier_id')
            ->selectRaw('chantier_id, SUM(co2_emission_kg) as total_kg')
            ->groupBy('chantier_id')
            ->with('chantier')
            ->get();

        $data = [];
        foreach ($transactions as $t) {
            $name = $t->chantier ? $t->chantier->name : 'Inconnu';
            $data[] = [
                'name' => $name,
                'total_kg' => $t->total_kg,
            ];
        }

        usort($data, fn ($a, $b) => $b['total_kg'] <=> $a['total_kg']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Exporter PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (DocumentService $documentService) {
                    $data = [
                        'dateFrom' => $this->dateFrom,
                        'dateTo' => $this->dateTo,
                        'totalCo2Kg' => $this->getTotalCo2(),
                        'byMonth' => $this->getCo2ByMonth(),
                        'byChantier' => $this->getCo2ByChantier(),
                    ];

                    $filename = 'bilan_carbone_rse_' . now()->format('Y_m_d_His');
                    
                    $path = $documentService->generate(
                        'filament.flottes.pdf.rse-report',
                        $data,
                        $filename,
                        'rapports'
                    );

                    return response()->download(storage_path("app/public/{$path}"));
                }),
        ];
    }
}
