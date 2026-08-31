<?php

namespace App\Providers;

use App\Contracts\Tiers\LegalDocumentProviderInterface;
use App\Models\Banque\BankReconciliation;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Locations\RentalContract;
use App\Observers\Banque\BankReconciliationObserver;
use App\Observers\Immobilisation\FixedAssetObserver;
use App\Observers\Locations\RentalContractObserver;
use App\Services\RH\GoogleCloudVisionOcrService;
use App\Services\RH\OcrServiceInterface;
use App\Services\Tiers\Providers\ApiEntrepriseProvider;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Carbon\CarbonImmutable;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OcrServiceInterface::class, GoogleCloudVisionOcrService::class);
        $this->app->bind(LegalDocumentProviderInterface::class, ApiEntrepriseProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        BankReconciliation::observe(BankReconciliationObserver::class);
        FixedAsset::observe(FixedAssetObserver::class);
        RentalContract::observe(RentalContractObserver::class);

        if (app()->isProduction() || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
            request()->server->set('HTTPS', 'on');
        }

        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            $panelSwitch
                ->modalHeading('Espaces')
                ->slideOver()
                ->panels([
                    'core',
                    'tiers',
                    'chantier',
                    'interventions',
                    'articles',
                    'commerce',
                    'banque',
                    'rh',
                    'paie',
                    'flottes',
                    'immobilisation',
                    'locations',
                    'gpao',
                ])
                ->icons([
                    'core' => Phosphor::Building,
                    'tiers' => Phosphor::Users,
                    'chantier' => Phosphor::HardHat,
                    'interventions' => Phosphor::Wrench,
                    'articles' => Phosphor::BoxArrowUp,
                    'commerce' => Phosphor::ShoppingBag,
                    'banque' => Phosphor::Bank,
                    'rh' => Phosphor::UsersThree,
                    'paie' => Phosphor::Certificate,
                    'flottes' => Phosphor::Truck,
                    'immobilisation' => Phosphor::BoxArrowUp,
                    'locations' => Phosphor::KeyReturn,
                    'gpao' => Phosphor::Factory,
                ])
                ->labels([
                    'core' => 'Configurations',
                    'tiers' => 'Tiers',
                    'chantier' => 'Chantiers',
                    'interventions' => 'Interventions',
                    'articles' => 'Articles & Stocks',
                    'commerce' => 'Commerces & Facturations',
                    'banque' => 'Banque & Rapprochement',
                    'rh' => 'Ressources Humaines',
                    'paie' => 'Paie',
                    'flottes' => 'Flottes',
                    'immobilisation' => 'Immobilisations',
                    'locations' => 'Locations',
                    'gpao' => 'Atelier & Production',
                ]);
        });

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            fn (): string => Blade::render('
                <link rel="manifest" href="/build/manifest.webmanifest">
                <meta name="theme-color" content="#f97316">
                <link rel="apple-touch-icon" href="/images/icon-192x192.png">
                <meta name="apple-mobile-web-app-capable" content="yes">
                <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
            '),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => Blade::render('@include("components.webpush-script")')
        );

        // Injection du script Service Worker à la fin du BODY
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string => Blade::render('
                <script>
                    if ("serviceWorker" in navigator) {
                        window.addEventListener("load", () => {
                            navigator.serviceWorker.register("/sw.js")
                                .then(reg => console.log("SW Filament enregistré !"))
                                .catch(err => console.log("Erreur SW Filament", err));
                        });
                    }
                </script>
            '),
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
