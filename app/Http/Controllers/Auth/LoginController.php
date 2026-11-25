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

    protected function credentials(Request $request): array
    {
        $credentials = $request->only($this->username(), 'password');
        $user = User::query()->where($this->username(), $request->input($this->username()))->first();

        if ($user) {
            // Évite qu’une adresse e-mail partagée fasse authentifier un autre
            // compte actif à la place du livreur désactivé saisi ici.
            $credentials['id'] = $user->id;
        }

        if ($user?->isLivreur()) {
            $credentials['active'] = true;
        }

        return $credentials;
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $user = User::query()
            ->where($this->username(), $request->input($this->username()))
            ->first();

        if ($user?->isLivreur() && ! $user->active) {
            return redirect()->back()
                ->withInput($request->only($this->username(), 'remember'))
                ->withErrors([
                    $this->username() => 'Ton compte livreur est désactivé. Demande au responsable boutique de te remettre des produits pour réactiver ton accès.',
                ]);
        }

        return parent::sendFailedLoginResponse($request);
    }

    /**
     * Redirige l'utilisateur selon son rôle après connexion.
     */
    protected function authenticated(Request $request, $user)
    {
        // Garde de sécurité après authentification : même si un autre mécanisme
        // fournit les identifiants, un livreur désactivé ne doit jamais garder
        // une session ouverte.
        if ($user instanceof User && $user->isLivreur() && ! $user->active) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Ton compte livreur est désactivé. Le responsable boutique doit te remettre des produits pour réactiver ton accès.',
            ]);
        }

        $homeUrl = $user instanceof User ? $user->homeUrl() : RouteServiceProvider::HOME;

        // Les comptes de vente grossiste doivent toujours retrouver leur espace,
        // même si la session garde une ancienne URL /Admin comme destination.
        if ($user instanceof User && $user->isWholesaleSalesRole()) {
            $request->session()->forget('url.intended');

            return redirect()->to($homeUrl);
        }

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
            if (in_array($user->user_type, [User::ROLE_PARTNER, User::ROLE_CALL_CENTER, User::ROLE_ACCOUNTANT, User::ROLE_SALES, User::ROLE_CASHIER, User::ROLE_COMMERCIAL_MANAGER, User::ROLE_WHOLESALER], true)) {
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
