<?php

namespace App\Filament\Banque\Resources\Accounting;

use App\Services\Accounting\CegidFlowExportService;
use App\Services\Accounting\FecExportService;
use App\Services\Accounting\Sage50ExportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class AccountingExportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?string $navigationLabel = 'Export Comptable';

    protected static ?string $title = 'Export Comptable';

    protected static ?string $slug = 'accounting-export';

    protected string $view = 'filament.pages.accounting-export';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'year' => date('Y'),
            'format' => 'fec',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('year')
                    ->label('Année')
                    ->options(collect(range(date('Y') - 5, date('Y') + 1))->flip())
                    ->required()
                    ->default(date('Y')),

                Forms\Components\Select::make('format')
                    ->label('Format d\'export')
                    ->options([
                        'fec' => 'FEC (Fichier des Écritures Comptables)',
                        'sage50' => 'Sage 50',
                        'cegid' => 'Cegid Flow',
                    ])
                    ->required()
                    ->default('fec'),
            ])
            ->statePath('data');
    }

    public function export(): void
    {
        $data = $this->data;
        $year = (int) $data['year'];
        $format = $data['format'];

        $path = match ($format) {
            'fec' => app(FecExportService::class)->exportFec($year),
            'sage50' => app(Sage50ExportService::class)->exportCsv($year),
            'cegid' => app(CegidFlowExportService::class)->exportCsv($year),
        };

        $filename = basename($path);

        $this->dispatch('export-ready', [
            'path' => $path,
            'filename' => $filename,
        ]);
    }

    public function getPreviewData(): array
    {
        $data = $this->data;
        $year = (int) $data['year'];
        $format = $data['format'] ?? 'fec';

        return match ($format) {
            'fec' => app(FecExportService::class)->getFecData($year),
            'sage50' => app(Sage50ExportService::class)->getData($year),
            'cegid' => app(CegidFlowExportService::class)->getData($year),
            default => ['header' => [], 'rows' => []],
        };
    }
}
