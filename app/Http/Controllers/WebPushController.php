<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebPushController extends Controller
{
    /**
     * Enregistrer l'abonnement webpush de l'utilisateur.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'endpoint'    => 'required',
            'keys.auth'   => 'required',
            'keys.p256dh' => 'required'
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];

        $user = $request->user();

        if ($user) {
            $user->updatePushSubscription($endpoint, $key, $token);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Not authenticated.'], 403);
    }
}
