<?php

namespace App\\Http\\Middleware;

use App\\Services\\Tiers\\AuthenticatedThirdPartyService;
use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class EnsureUserIsCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        AuthenticatedThirdPartyService $thirdPartyService
    ): Response {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('filament.customer.auth.login');
        }

        if (! $thirdPartyService->customer($user)) {
            auth()->logout();

            return redirect()->route('filament.customer.auth.login')
                ->with('error', 'Votre compte n\'est pas relié à une fiche client active.');
        }

        return $next($request);
    }
}
