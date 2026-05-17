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

        // 1. Vérification de l'authentification de base
        if (! $user) {
            return redirect()->route('filament.terrain.auth.login');
        }

        // 2. Vérification de la liaison avec la fiche employé RH
        $employee = $user->salarie; // Relation Employee active issue du module RH

        if (! $employee || ! $employee->is_active) {
            auth()->logout();

            return redirect()->route('filament.terrain.auth.login')
                ->with('error', 'Votre compte n\'est pas relié à une fiche employé active.');
        }

        // 3. Vérification du rôle / poste (ex: Chef d'équipe, Chef de chantier, Conducteur de travaux)
        $jobTitle = strtolower($employee->currentContract?->job_title ?? '');
        $allowedKeywords = ['chef', 'conducteur', 'foreman', 'encadrant', 'responsable'];

        $isAuthorized = false;
        foreach ($allowedKeywords as $keyword) {
            if (str_contains($jobTitle, $keyword)) {
                $isAuthorized = true;
                break;
            }
        }

        // Optionnel : On peut aussi autoriser les super-admins à des fins de test/support
        if ($user->email === 'admin@admin.com') {
            $isAuthorized = true;
        }

        if (! $isAuthorized) {
            auth()->logout();

            return redirect()->route('filament.terrain.auth.login')
                ->with('error', 'Accès réservé aux chefs de chantier et conducteurs de travaux.');
        }

        return $next($request);
    }
}
