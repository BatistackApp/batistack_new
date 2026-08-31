<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsChefDeChantier;
use App\Providers\Filament\Traits\HasKnowledgeBaseCompanion;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Terrain\Pages\TerrainDashboard as Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Guava\FilamentKnowledgeBase\Plugins\KnowledgeBaseCompanionPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MartinPetricko\FilamentSentryFeedback\FilamentSentryFeedbackPlugin;
use Vaslv\FilamentAppVersion\AppVersionPlugin;

class TerrainPanelProvider extends PanelProvider
{
    use HasKnowledgeBaseCompanion;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('terrain')
            ->path('terrain')
            ->colors([
                'primary' => Color::Emerald, // Vert robuste "Sécurité / Terrain"
                'gray' => Color::Stone,
            ])
            ->login()
            ->topNavigation()
            ->databaseNotifications() // Alertes directes en cas de consignes
            ->brandName('Batistack - Terrain')
            ->brandLogo(asset('images/chantiers.png'))
            ->discoverResources(in: app_path('Filament/Terrain/Resources'), for: 'App\Filament\Terrain\Resources')
            ->discoverPages(in: app_path('Filament/Terrain/Pages'), for: 'App\Filament\Terrain\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Terrain/Widgets'), for: 'App\Filament\Terrain\Widgets')
            ->plugin(KnowledgeBaseCompanionPlugin::make()->knowledgeBasePanelId('docs'))
            ->plugins([
                FilamentSentryFeedbackPlugin::make(),
                AppVersionPlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsChefDeChantier::class,
            ]);
    }
}
