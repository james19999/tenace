<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de contrôle d'accès par rôle (user_type).
 *
 * Usage dans les routes :
 *   ->middleware('role:ADMINUSER,CALLCENTER')
 *
 * Passer plusieurs rôles séparés par une virgule pour autoriser l'accès à l'un ou l'autre.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Vérification que l'utilisateur a l'un des rôles autorisés
        if (! in_array($user->user_type, $roles, true)) {
            // Si l'utilisateur est connecté mais n'a pas le bon rôle,
            // on le redirige vers sa page d'accueil naturelle avec un message d'erreur
            return redirect()
                ->to($this->homeForUser($user))
                ->with('error', 'Vous n\'avez pas accès à cette page.');
        }

        return $next($request);
    }

    /**
     * Retourne la page d'accueil selon le rôle de l'utilisateur.
     */
    private function homeForUser($user): string
    {
        return match ($user->user_type) {
            'CALLCENTER' => route('costumer.follow-up'),
            default      => route('Admin'),
        };
    }
}
