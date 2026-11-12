<?php

namespace App\\Http\\Middleware;

use App\\Services\\Tiers\\AuthenticatedThirdPartyService;
use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class EnsureUserIsSubcontractor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('filament.sous-traitant.auth.login');
        }

        if (! app(AuthenticatedThirdPartyService::class)->subcontractor($user)) {
            abort(403, 'Accès non autorisé : compte sous-traitant inactif ou non autorisé.');
        }

        return $next($request);
    }
}
