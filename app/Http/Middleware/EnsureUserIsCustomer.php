<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCustomer
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
            return redirect()->route('filament.customer.auth.login');
        }

        $client = $user->customer;

        if (! $client || ! $client->is_active) {
            auth()->logout();

            return redirect()->route('filament.customer.auth.login')
                ->with('error', 'Votre compte n\'est pas relié à une fiche client active.');
        }

        return $next($request);
    }
}
