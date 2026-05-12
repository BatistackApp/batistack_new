<?php

namespace App\Filament\Widgets;

use App\Services\Core\SettingService;
use Filament\Widgets\Widget;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SystemHealthWidget extends Widget
{
    protected string $view = 'filament.widgets.system-health-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws CircularDependencyException
     * @throws EntryNotFoundException
     */
    public function getSystemStatus(): array
    {
        $settingService = app(SettingService::class);

        return [
            [
                'label' => 'Google Maps API',
                'status' => ! empty($settingService->get('google_maps_key')),
                'description' => 'Requis pour la géolocalisation des chantiers.',
                'color' => ! empty($settingService->get('google_maps_key')) ? 'green' : 'red',
            ],
            [
                'label' => 'Service SIREN',
                'status' => ! empty($settingService->get('siren_api_key')),
                'description' => 'Requis pour l\'importation automatique des tiers.',
            ],
            [
                'label' => 'Service OCR',
                'status' => $settingService->get('ocr_enabled') === 'true' && ! empty($settingService->get('ocr_google_key')),
                'description' => 'Requis pour la lecture des factures et notes de frais.',
            ],
        ];
    }
}
