<?php

namespace App\Filament\RH\Pages;

use App\Services\RH\CibtpService;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ExportDNA extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = Phosphor::Files;

    protected static string|\UnitEnum|null $navigationGroup = 'Déclarations & Exports';

    protected static ?string $navigationLabel = 'Export DNA (CIBTP)';

    protected static ?string $title = 'Déclaration Nominative Annuelle (CIBTP)';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.rh.pages.export-dna';

    public ?int $reference_year = null;

    public function mount(): void
    {
        $this->form->fill([
            'reference_year' => now()->year,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $years = [];
        $currentYear = now()->year;
        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
            $years[$i] = 'Période Avril '.($i - 1)." - Mars $i";
        }

        return $schema
            ->components([
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
