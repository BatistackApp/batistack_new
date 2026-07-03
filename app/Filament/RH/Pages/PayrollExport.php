<?php

namespace App\Filament\RH\Pages;

use App\Services\RH\PayrollExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class PayrollExport extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.rh.pages.payroll-export';

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return 'heroicon-o-document-arrow-down';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Gestion';
    }

    public static function getNavigationLabel(): string
    {
        return 'Export Paie Mensuel';
    }

    public function getTitle(): string
    {
        return 'Export Paie Mensuel';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'month' => now()->month,
            'year' => now()->year,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('month')
                    ->label('Mois')
                    ->options([
                        1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                        4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
                        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
                        10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                    ])
                    ->required()
                    ->native(false),
                Select::make('year')
                    ->label('Année')
                    ->options(array_combine(range(now()->year - 2, now()->year + 1), range(now()->year - 2, now()->year + 1)))
                    ->required()
                    ->native(false),
            ])
            ->statePath('data');
    }

    public function exportAction(): Action
    {
        return Action::make('export')
            ->label('Générer Export CSV')
            ->color('success')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function () {
                $data = $this->form->getState();
                $service = app(PayrollExportService::class);

                return response()->streamDownload(function () use ($service, $data) {
                    echo $service->generateCsv((int) $data['month'], (int) $data['year']);
                }, "export_paie_{$data['year']}_{$data['month']}.csv");
            });
    }
}
