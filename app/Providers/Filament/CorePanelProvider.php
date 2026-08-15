<?php

namespace App\Providers\Filament;

use AchyutN\FilamentLogViewer\FilamentLogViewer;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Providers\Filament\Traits\HasKnowledgeBaseCompanion;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Croustibat\FilamentJobsMonitor\FilamentJobsMonitorPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use FilamentInbox\FilamentInboxPlugin;
use Guava\FilamentKnowledgeBase\Plugins\KnowledgeBaseCompanionPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaBoiteACode\FilamentDashboardWidgets\FilamentDashboardWidgetsPlugin;
use MartinPetricko\FilamentSentryFeedback\FilamentSentryFeedbackPlugin;
use Vaslv\FilamentAppVersion\AppVersionPlugin;

class CorePanelProvider extends PanelProvider
{
    use HasKnowledgeBaseCompanion;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('core')
            ->path('core')
            ->viteTheme('resources/css/filament/core/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): string => Blade::render('@livewire(\'release-notes-button\')'),
            )
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
            ->plugins([
                FilamentSentryFeedbackPlugin::make(),
                AppVersionPlugin::make(),
                FilamentShieldPlugin::make(),
                FilamentInboxPlugin::make(),
                FilamentLogViewer::make(),
                AuthDesignerPlugin::make()
                    ->login(),
                FilamentJobsMonitorPlugin::make(),
                FilamentDashboardWidgetsPlugin::make(),
            ])
            ->databaseNotifications()
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsAdmin::class,
            ]);
    }
}
