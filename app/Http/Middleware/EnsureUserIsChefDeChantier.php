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

        // Vérification de la liaison avec la fiche employé RH
        $employee = $user->salarie;

        if (! $employee || ! $employee->is_active) {
            auth()->logout();

            return redirect()->route('filament.terrain.auth.login')
                ->with('error', 'Votre compte n\'est pas relié à une fiche employé active.');
        }

        // Les administrateurs sont identifiés par le flag applicatif, pas par une adresse e-mail.
        $isAuthorized = (bool) $user->is_admin;

        if (! $isAuthorized) {
            $jobTitle = strtolower($employee->currentContract?->job_title ?? '');
            $allowedKeywords = ['chef', 'conducteur', 'foreman', 'encadrant', 'responsable'];

            foreach ($allowedKeywords as $keyword) {
                if (str_contains($jobTitle, $keyword)) {
                    $isAuthorized = true;
                    break;
                }
            }
        }

        if (! $isAuthorized) {
            auth()->logout();

            return redirect()->route('filament.terrain.auth.login')
                ->with('error', 'Accès réservé aux chefs de chantier et conducteurs de travaux.');
        }

        return $next($request);
    }
}
