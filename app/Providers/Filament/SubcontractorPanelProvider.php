<?php

namespace App\Providers\Filament;

use App\Filament\Subcontractor\Pages\Dashboard;
use App\Filament\Subcontractor\Widgets\SubcontractorDashboardWidget;
use App\Http\Middleware\EnsureUserIsSubcontractor;
use App\Providers\Filament\Traits\HasKnowledgeBaseCompanion;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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

class SubcontractorPanelProvider extends PanelProvider
{
    use HasKnowledgeBaseCompanion;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sous-traitant')
            ->path('sous-traitant')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->brandName('Batistack - Espace Sous Traitant')
            ->colors([
                'primary' => Color::Purple,
            ])
            ->discoverResources(in: app_path('Filament/Subcontractor/Resources'), for: 'App\Filament\Subcontractor\Resources')
            ->discoverPages(in: app_path('Filament/Subcontractor/Pages'), for: 'App\Filament\Subcontractor\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Subcontractor/Widgets'), for: 'App\Filament\Subcontractor\Widgets')
            ->widgets([
                SubcontractorDashboardWidget::class,
            ])
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
                EnsureUserIsSubcontractor::class,
            ]);
    }
}
