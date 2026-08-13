<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsAdmin;
use Ariefng\FilamentCalculator\CalculatorPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Hydrat\TableLayoutToggle\TableLayoutTogglePlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ArticlesPanelProvider extends PanelProvider
{
    use \App\Providers\Filament\Traits\HasKnowledgeBaseCompanion;
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('articles')
            ->path('articles')
            ->login()
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->brandName('Batistack - Articles & Stocks')
            ->brandLogo(asset('images/rh.png'))
            ->colors([
                'primary' => Color::Amber, // Couleur représentative du matériel/stock
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Articles/Resources'), for: 'App\Filament\Articles\Resources')
            ->discoverPages(in: app_path('Filament/Articles/Pages'), for: 'App\Filament\Articles\Pages')
            ->discoverWidgets(in: app_path('Filament/Articles/Widgets'), for: 'App\Filament\Articles\Widgets')
            ->plugins([
                \MartinPetricko\FilamentSentryFeedback\FilamentSentryFeedbackPlugin::make(),
                CalculatorPlugin::make(),
                TableLayoutTogglePlugin::make()
                    ->setDefaultLayout('list')
                    ->displayToggleAction(true),
                \LaBoiteACode\FilamentDashboardWidgets\FilamentDashboardWidgetsPlugin::make(),
            ])
            ->plugin(\Guava\FilamentKnowledgeBase\Plugins\KnowledgeBaseCompanionPlugin::make()->knowledgeBasePanelId('docs'))
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
                EnsureUserIsAdmin::class,
            ]);
    }
}



