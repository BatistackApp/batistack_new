<?php

namespace App\Services\Core;

use Illuminate\Http\Request;

class DeviceDetectorService
{
    protected string $userAgent;

    public function __construct(protected Request $request)
    {
        $this->userAgent = $request->header('User-Agent', '');
    }

    /**
     * Détermine si l'utilisateur est sur un support mobile.
     */
    public function isMobile(): bool
    {
        return (bool) preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $this->userAgent);
    }

    /**
     * Détermine si l'utilisateur est sur une tablette.
     */
    public function isTablet(): bool
    {
        // Une tablette est souvent identifiée comme mobile mais avec des caractéristiques spécifiques (ex: iPad)
        return (bool) preg_match('/ipad|playbook|silk/i', $this->userAgent) ||
            ((bool) preg_match('/android/i', $this->userAgent) && ! (bool) preg_match('/mobile/i', $this->userAgent));
    }

    /**
     * Détermine si l'utilisateur est sur un ordinateur de bureau.
     */
    public function isDesktop(): bool
    {
        return ! $this->isMobile() && ! $this->isTablet();
    }

    /**
     * Retourne le type de support sous forme de chaîne de caractères.
     */
    public function getDeviceType(): string
    {
        if ($this->isMobile()) {
            return 'mobile';
        }
        if ($this->isTablet()) {
            return 'tablet';
        }

        return 'desktop';
    }
}
