<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('filament.core.auth.login');
        }

        if (! $user->is_admin) {
            return redirect()->route('filament.core.auth.login')
                ->with('error', 'Vous ne pouvez pas accéder à cette espace !');
        }

        return $next($request);
    }
}
