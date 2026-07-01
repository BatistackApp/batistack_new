<?php

namespace App\Http\Middleware;

use App\Enums\Tiers\ThirdPartyType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSubcontractor
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
            return redirect()->route('filament.sous-traitant.auth.login');
        }

        if (! $user->is_tiers) {
            abort(403, 'Accès non autorisé : Vous n\'êtes pas un Tiers.');
        }

        $contact = $user->contact;
        $thirdParty = $contact?->thirdParty;

        if (! $contact || ! $contact->is_active || ! $thirdParty) {
            abort(403, 'Accès non autorisé : Compte inactif ou non lié à une entreprise.');
        }

        // Vérifier si l'entreprise parente est bien un sous-traitant
        if ($thirdParty->type !== ThirdPartyType::SUBCONTRACTOR) {
            abort(403, 'Accès non autorisé : Cette section est réservée aux Sous-Traitants.');
        }

        return $next($request);
    }
}
