<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\ArticlesPanelProvider;
use App\Providers\Filament\BanquePanelProvider;
use App\Providers\Filament\ChantierPanelProvider;
use App\Providers\Filament\CommercePanelProvider;
use App\Providers\Filament\CorePanelProvider;
use App\Providers\Filament\CustomerPanelProvider;
use App\Providers\Filament\DocsPanelProvider;
use App\Providers\Filament\FlottesPanelProvider;
use App\Providers\Filament\GpaoPanelProvider;
use App\Providers\Filament\ImmobilisationPanelProvider;
use App\Providers\Filament\InterventionsPanelProvider;
use App\Providers\Filament\LocationsPanelProvider;
use App\Providers\Filament\PaiePanelProvider;
use App\Providers\Filament\RHPanelProvider;
use App\Providers\Filament\SalariePanelProvider;
use App\Providers\Filament\SubcontractorPanelProvider;
use App\Providers\Filament\TechnicienPanelProvider;
use App\Providers\Filament\TerrainPanelProvider;
use App\Providers\Filament\TiersPanelProvider;
use App\Providers\Filament\Vision3DPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    ArticlesPanelProvider::class,
    BanquePanelProvider::class,
    ChantierPanelProvider::class,
    CommercePanelProvider::class,
    CorePanelProvider::class,
    CustomerPanelProvider::class,
    FlottesPanelProvider::class,
    GpaoPanelProvider::class,
    ImmobilisationPanelProvider::class,
    InterventionsPanelProvider::class,
    LocationsPanelProvider::class,
    PaiePanelProvider::class,
    RHPanelProvider::class,
    SalariePanelProvider::class,
    SubcontractorPanelProvider::class,
    TechnicienPanelProvider::class,
    TerrainPanelProvider::class,
    TiersPanelProvider::class,
    Vision3DPanelProvider::class,
    DocsPanelProvider::class,
    FortifyServiceProvider::class,
];
