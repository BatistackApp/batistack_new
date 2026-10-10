<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsChefDeChantier
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Vérification de l'authentification de base
        if (! $user) {
            return redirect()->route('filament.terrain.auth.login');
        }

        // Les administrateurs disposent du contournement validé pour Terrain.
        if ($user->is_admin) {
            return $next($request);
        }

        // Vérification de la liaison avec la fiche employé RH
        $employee = $user->salarie;

        if (! $employee || ! $employee->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Votre compte doit être relié à une fiche employé active.'], 403);
            }

            auth()->logout();

            return redirect()->route('filament.terrain.auth.login')
                ->with('error', 'Votre compte n\'est pas relié à une fiche employé active.');
        }

        // Les autres comptes sont autorisés selon leur intitulé de poste.
        $jobTitle = strtolower($employee->currentContract?->job_title ?? '');
        $allowedKeywords = ['chef', 'conducteur', 'foreman', 'encadrant', 'responsable'];
        $isAuthorized = false;

        foreach ($allowedKeywords as $keyword) {
            if (str_contains($jobTitle, $keyword)) {
                $isAuthorized = true;
                break;
            }
        }

        if (! $isAuthorized) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès réservé aux chefs de chantier et conducteurs de travaux.'], 403);
            }

            auth()->logout();

            return redirect()->route('filament.terrain.auth.login')
                ->with('error', 'Accès réservé aux chefs de chantier et conducteurs de travaux.');
        }

        return $next($request);
    }
}
