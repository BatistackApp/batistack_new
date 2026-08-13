<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ImmobilisationPanelProvider extends PanelProvider
{
    use \App\Providers\Filament\Traits\HasKnowledgeBaseCompanion;
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('immobilisation')
            ->path('immobilisations')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->brandName('Batistack - Immobilisations')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->discoverResources(in: app_path('Filament/Immobilisation/Resources'), for: 'App\Filament\Immobilisation\Resources')
            ->discoverPages(in: app_path('Filament/Immobilisation/Pages'), for: 'App\Filament\Immobilisation\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Immobilisation/Widgets'), for: 'App\Filament\Immobilisation\Widgets')
            ->plugin(\Guava\FilamentKnowledgeBase\Plugins\KnowledgeBaseCompanionPlugin::make()->knowledgeBasePanelId('docs'))
            ->plugins([
                \MartinPetricko\FilamentSentryFeedback\FilamentSentryFeedbackPlugin::make(),
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



