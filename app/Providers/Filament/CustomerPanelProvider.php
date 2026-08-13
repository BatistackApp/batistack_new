<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureUserIsCustomer;
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

class CustomerPanelProvider extends PanelProvider
{
    use \App\Providers\Filament\Traits\HasKnowledgeBaseCompanion;
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('customer')
            ->path('customer')
            ->viteTheme('resources/css/filament/customer/theme.css')
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Stone,
            ])
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications() // Alertes directes en cas de consignes
            ->brandName('Batistack - Espace Client')
            ->discoverResources(in: app_path('Filament/Customer/Resources'), for: 'App\Filament\Customer\Resources')
            ->discoverPages(in: app_path('Filament/Customer/Pages'), for: 'App\Filament\Customer\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Customer/Widgets'), for: 'App\Filament\Customer\Widgets')
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
                EnsureUserIsCustomer::class,
            ]);
    }
}



