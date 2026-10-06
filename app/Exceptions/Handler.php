<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     * Redirect to login with a message when CSRF token has expired (419).
     */
    public function render($request, Throwable $exception)
    {
        // Quand le token CSRF a expiré (erreur 419)
        if ($exception instanceof TokenMismatchException) {

            // Requête AJAX / Livewire → réponse JSON pour forcer le rechargement
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Session expirée. Veuillez vous reconnecter.',
                    'redirect' => route('login'),
                ], 419);
            }

            // Requête normale → rediriger vers le login avec un message flash
            return redirect()->route('login')
                ->with('session_expired', 'Votre session a expiré. Veuillez vous reconnecter.');
        }

        return parent::render($request, $exception);
    }
}
