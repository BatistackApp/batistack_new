<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsSubcontractor;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SubcontractorPanelProvider extends PanelProvider
{
    use \App\Providers\Filament\Traits\HasKnowledgeBaseCompanion;
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
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
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
                EnsureUserIsSubcontractor::class,
            ]);
    }
}



