<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\ArticlesPanelProvider;
use App\Providers\Filament\ChantierPanelProvider;
use App\Providers\Filament\CommercePanelProvider;
use App\Providers\Filament\CorePanelProvider;
use App\Providers\Filament\CustomerPanelProvider;
use App\Providers\Filament\FlottesPanelProvider;
use App\Providers\Filament\RHPanelProvider;
use App\Providers\Filament\SalariePanelProvider;
use App\Providers\Filament\SubcontractorPanelProvider;
use App\Providers\Filament\TerrainPanelProvider;
use App\Providers\Filament\TiersPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    ArticlesPanelProvider::class,
    ChantierPanelProvider::class,
    CommercePanelProvider::class,
    CorePanelProvider::class,
    CustomerPanelProvider::class,
    SalariePanelProvider::class,
    FlottesPanelProvider::class,
    RHPanelProvider::class,
    TerrainPanelProvider::class,
    TiersPanelProvider::class,
    SubcontractorPanelProvider::class,
    FortifyServiceProvider::class,
];
