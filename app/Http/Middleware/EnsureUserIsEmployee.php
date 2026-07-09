<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEmployee
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
            return redirect()->route('filament.salarie.auth.login');
        }

        $employee = $user->salarie;

        if (! $employee || ! $employee->is_active) {
            auth()->logout();

            return redirect()->route('filament.terrain.auth.login')
                ->with('error', 'Votre compte n\'est pas relié à une fiche employé active.');
        }

        return $next($request);
    }
}
