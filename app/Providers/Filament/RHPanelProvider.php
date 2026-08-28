<?php

namespace App\Providers\Filament;

use App\Filament\RH\Pages\SecuriteDashboard;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Providers\Filament\Traits\HasKnowledgeBaseCompanion;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Guava\Calendar\CalendarPlugin;
use Guava\FilamentKnowledgeBase\Plugins\KnowledgeBaseCompanionPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MartinPetricko\FilamentSentryFeedback\FilamentSentryFeedbackPlugin;
use Vaslv\FilamentAppVersion\AppVersionPlugin;

class RHPanelProvider extends PanelProvider
{
    use HasKnowledgeBaseCompanion;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('rh')
            ->path('rh')
            ->viteTheme('resources/css/filament/rh/theme.css')
            ->colors([
                'primary' => Color::Indigo, // Couleur distinctive pour le module RH
                'gray' => Color::Slate,
            ])
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->brandName('Batistack - Ressources Humaines')
            ->brandLogo(asset('images/rh.png'))
            ->discoverResources(in: app_path('Filament/RH/Resources'), for: 'App\Filament\RH\Resources')
            ->discoverPages(in: app_path('Filament/RH/Pages'), for: 'App\Filament\RH\Pages')
            ->pages([
                Dashboard::class,
                SecuriteDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/RH/Widgets'), for: 'App\Filament\RH\Widgets')
            ->plugins([
                FilamentSentryFeedbackPlugin::make(),
                AppVersionPlugin::make(),
                CalendarPlugin::make(),
            ])
            ->plugin(KnowledgeBaseCompanionPlugin::make()->knowledgeBasePanelId('docs'))
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
