<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTechnician
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
            return redirect()->route('filament.technicien.auth.login');
        }

        // Vérifier d'abord s'il a un profil employé actif (comme pour EnsureUserIsEmployee)
        $employee = $user->salarie;

        if (! $employee || ! $employee->is_active) {
            auth()->logout();

            return redirect()->route('filament.technicien.auth.login')
                ->with('error', 'Votre compte n\'est pas relié à une fiche employé active.');
        }

        // Vérifier l'accès technicien
        if (! $user->access_technique) {
            auth()->logout();

            return redirect()->route('filament.technicien.auth.login')
                ->with('error', 'Vous n\'avez pas les droits d\'accès à l\'Espace Technicien SAV.');
        }

        return $next($request);
    }
}
