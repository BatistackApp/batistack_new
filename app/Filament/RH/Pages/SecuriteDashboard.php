<?php

namespace App\Filament\RH\Pages;

use App\Models\Core\Company;
use App\Services\Core\DocumentService;
use App\Services\RH\SafetyRateService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Widgets\Widget;

class SecuriteDashboard extends Page
{
    protected static ?string $title = 'Tableau de Bord Sécurité (AT/MP)';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'RH';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.rh.pages.securite-dashboard';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exporter')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(function () {
                    $rates = app(SafetyRateService::class)->rollingYear();
                    $company = auth()->user()->company ?? Company::first();

                    $path = app(DocumentService::class)->generate(
                        'pdf.rh.securite_taux',
                        [
                            'company' => $company,
                            'rates' => $rates,
                            'title' => 'Indicateurs de Sécurité (AT)',
                            'generated_at' => now()->format('d/m/Y H:i'),
                        ],
                        'securite_taux_'.now()->format('Ymd_His'),
                        'rh'
                    );

                    return app(DocumentService::class)->download($path);
                }),
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return [];
    }
}
