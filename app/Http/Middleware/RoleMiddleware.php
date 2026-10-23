<?php

namespace App\Http\Middleware;

use App\Models\User;
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
            $destination = $this->homeForUser($user);

            // Empêche toute boucle de redirection infinie vers la même route
            $destinationPath = parse_url($destination, PHP_URL_PATH) ?? $destination;
            $currentPath = parse_url($request->url(), PHP_URL_PATH) ?? $request->url();
            if (rtrim($currentPath, '/') === rtrim($destinationPath, '/')) {
                abort(403, 'Vous n\'avez pas accès à cette page.');
            }

            // Si l'utilisateur est connecté mais n'a pas le bon rôle,
            // on le redirige vers sa page d'accueil naturelle avec un message d'erreur
            return redirect()
                ->to($destination)
                ->with('error', 'Vous n\'avez pas accès à cette page.');
        }

        return $next($request);
    }

    /**
     * Retourne la page d'accueil selon le rôle de l'utilisateur.
     */
    public function homeForUser($user): string
    {
        if ($user instanceof User) {
            return $user->homeUrl();
        }

        return route('Admin');
    }
}
