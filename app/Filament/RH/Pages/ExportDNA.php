<?php

namespace App\Filament\RH\Pages;

use App\Services\RH\CibtpService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ExportDNA extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = Phosphor::Files;
    protected static string | \UnitEnum | null $navigationGroup = 'Exports & Rapports';
    protected static string | null $navigationLabel = 'Export DNA (CIBTP)';
    protected static string | null $title = 'Déclaration Nominative Annuelle (CIBTP)';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.rh.pages.export-dna';

    public ?int $reference_year = null;

    public function mount(): void
    {
        $this->form->fill([
            'reference_year' => now()->year,
        ]);
    }

    public function form(Form $form): Form
    {
        $years = [];
        $currentYear = now()->year;
        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
            $years[$i] = "Période Avril " . ($i - 1) . " - Mars $i";
        }

        return $form
            ->schema([
                Select::make('reference_year')
                    ->label('Année de Référence de la Déclaration')
                    ->options($years)
                    ->required()
                    ->native(false)
                    ->helperText('Sélectionnez l\'année de la fin de la période de référence (Ex: 2026 pour la période Avril 2025 à Mars 2026).'),
            ]);
    }

    public function downloadDNA()
    {
        $data = $this->form->getState();
        $year = $data['reference_year'];

        $csvContent = app(CibtpService::class)->generateDNA($year);

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, "DNA_CIBTP_{$year}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
