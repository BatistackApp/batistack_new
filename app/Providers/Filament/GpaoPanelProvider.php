<?php

namespace App\Providers\Filament;

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
use Guava\FilamentKnowledgeBase\Plugins\KnowledgeBaseCompanionPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MartinPetricko\FilamentSentryFeedback\FilamentSentryFeedbackPlugin;
use Vaslv\FilamentAppVersion\AppVersionPlugin;

class GpaoPanelProvider extends PanelProvider
{
    use HasKnowledgeBaseCompanion;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('gpao')
            ->path('gpao')
            ->viteTheme('resources/css/filament/gpao/theme.css')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->brandName('Batistack - Atelier & Production')
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Gpao'), for: 'App\Filament\Gpao')
            ->discoverPages(in: app_path('Filament/Gpao'), for: 'App\Filament\Gpao')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Gpao/Widgets'), for: 'App\Filament\Gpao\Widgets')
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
                EnsureUserIsAdmin::class,
            ]);
    }
}
