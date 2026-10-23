<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Redirige l'utilisateur selon son rôle après connexion.
     */
    protected function authenticated(Request $request, $user)
    {
        $homeUrl = $user instanceof User ? $user->homeUrl() : RouteServiceProvider::HOME;

        $intended = $request->session()->get('url.intended');
        if ($intended) {
            $intendedPath = parse_url($intended, PHP_URL_PATH) ?? '';
            // Si l'URL intended est /Admin mais que l'utilisateur n'a pas accès à /Admin, oublier intended
            if (str_ends_with(rtrim($intendedPath, '/'), '/Admin')) {
                if (! in_array($user->user_type, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_SECRETARY, User::ROLE_LIVREUR], true)) {
                    $request->session()->forget('url.intended');
                }
            }

            // Pour ces rôles ayant des espaces dédiés, toujours privilégier leur espace
            if (in_array($user->user_type, [User::ROLE_PARTNER, User::ROLE_CALL_CENTER, User::ROLE_ACCOUNTANT, User::ROLE_SALES, User::ROLE_CASHIER], true)) {
                $request->session()->forget('url.intended');
            }
        }

        return redirect()->intended($homeUrl);
    }

    /** Redirige toujours vers la page d'accueil après la déconnexion. */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
