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
        if ($user->user_type === User::ROLE_CALL_CENTER) {
            // On vide l'URL "intended" stockée en session (souvent /Admin)
            // pour éviter qu'elle n'écrase notre redirection call-center.
            $request->session()->forget('url.intended');

            return redirect('/costumer/follow-up');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
