<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class Vision3DPanelProvider extends PanelProvider
{
    use \App\Providers\Filament\Traits\HasKnowledgeBaseCompanion;
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vision3d')
            ->path('vision3d')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->brandName('Batistack - Vision 3D')
            ->brandLogo(asset('images/vision3d.png')) // Utilisera le logo par défaut ou cassé si non existant, à remplacer par le client si besoin
            ->colors([
                'primary' => Color::Sky,
                'gray' => Color::Slate,
            ])
            ->pages([
                \App\Filament\Vision3D\Pages\Dashboard::class,
            ])
            ->discoverResources(in: app_path('Filament/Vision3D/Resources'), for: 'App\Filament\Vision3D\Resources')
            ->discoverPages(in: app_path('Filament/Vision3D/Pages'), for: 'App\Filament\Vision3D\Pages')
            ->discoverWidgets(in: app_path('Filament/Vision3D/Widgets'), for: 'App\Filament\Vision3D\Widgets')
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => \Illuminate\Support\Facades\Blade::render("@vite('resources/js/app.js')")
            )
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



