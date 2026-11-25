<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCourier
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isLivreur() && ! $user->active) {
            return response()->json([
                'status' => false,
                'messages' => 'Ton compte livreur est désactivé. Le responsable boutique doit te remettre des produits pour réactiver ton accès.',
            ], 403);
        }

        return $next($request);
    }
}
