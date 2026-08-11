<?php

namespace App\Filament\Chantier\Resources\Chantiers\Schemas;

use App\Services\Chantiers\ChantierAnalyticService;
use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Layers\Marker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')->label('Désignation')->weight('bold')->size('lg'),
                                TextEntry::make('reference')->label('Référence')->fontFamily('mono'),
                                TextEntry::make('status')->label('Statut')->badge(),
                                TextEntry::make('manager.full_name')->label('Responsable')->icon(Phosphor::UserGear),
                            ]),
                    ]),

                Tabs::make('Analyse & Pilotage')
                    ->tabs([
                        Tabs\Tab::make('Performances')
                            ->icon(Phosphor::ChartLineUp)
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('metrics_progress')
                                            ->label('Avancement Technique')
                                            ->getStateUsing(fn ($record, ChantierAnalyticService $service) => $service->getPerformanceMetrics($record)['progress'].' %')
                                            ->weight('bold')
                                            ->color('primary'),
                                        TextEntry::make('metrics_hours')
                                            ->label('Consommation Heures')
                                            ->getStateUsing(fn ($record, ChantierAnalyticService $service) => $service->getPerformanceMetrics($record)['hours']['real'].' / '.$record->budget_hours.' h')
                                            ->color(fn ($record) => $record->real_hours > $record->budget_hours ? 'danger' : 'success'),
                                        TextEntry::make('metrics_margin')
                                            ->label('Marge estimée')
                                            ->getStateUsing(function ($record, ChantierAnalyticService $service) {
                                                $m = $service->getPerformanceMetrics($record);

                                                return number_format($m['financials']['budget_ht'] - $m['financials']['total_cost_real'], 2).' €';
                                            })
                                            ->weight('bold'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Équipe assignée')
                            ->icon(Phosphor::UsersThree)
                            ->schema([
                                RepeatableEntry::make('members')
                                    ->label('Personnel sur site')
                                    ->schema([
                                        TextEntry::make('full_name')->label('Nom'),
                                        TextEntry::make('currentContract.job_title')->label('Poste'),
                                    ])->grid(3),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
