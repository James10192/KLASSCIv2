<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use RuntimeException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        $isAjax = $request->expectsJson()
            || $request->ajax()
            || str_contains($request->path(), 'ajax')
            || str_contains($request->path(), 'api/');

        if ($e instanceof TokenMismatchException) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre session a expiré. Veuillez rafraîchir la page et réessayer.',
                ], 419);
            }

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('warning', 'Votre session a expiré. Veuillez réessayer.');
        }

        if ($e instanceof UnauthorizedException) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas la permission d\'effectuer cette action.',
                ], 403);
            }

            // Guests (non-authenticated) hitting a role/permission gate should be
            // redirected to /login rather than receiving a confusing 403/404.
            // Spatie throws UnauthorizedException::notLoggedIn() before auth() check,
            // which without this branch falls through to parent::render() and can
            // surface as 404 depending on edge caching / fallback route resolution.
            if (! auth()->check()) {
                return redirect()->guest(route('login'));
            }

            // Authenticated but missing the required role/permission → 403 page.
            return response()->view('errors.403', ['exception' => $e], 403);
        }

        if ($e instanceof CoefficientMissingException) {
            $context = $e->getContext();
            $classeId = $context['classe']['id'] ?? null;
            $matiereId = $context['matiere']['id'] ?? null;

            $context['config_url'] = route('esbtp.evaluations.index', ['open_coefficients' => 1]);
            if ($classeId) {
                $context['classe_matieres_url'] = \Illuminate\Support\Facades\Route::has('classes.matieres')
                    ? route('classes.matieres', ['classe' => $classeId])
                    : route('esbtp.evaluations.index', ['open_coefficients' => 1]);
            }
            if ($classeId || $matiereId) {
                $query = array_filter([
                    'classe_id' => $classeId,
                    'matiere_id' => $matiereId,
                ], fn ($value) => ! is_null($value) && $value !== '');
                $context['evaluations_url'] = route('esbtp.evaluations.index', $query);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'context' => $context,
                ], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage())
                ->with('coefficient_missing_context', $context);
        }

        if ($e instanceof RuntimeException) {
            $message = $e->getMessage();

            if (str_starts_with($message, 'Coefficient manquant') || str_starts_with($message, 'Classe invalide pour le calcul du coefficient.')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                // Garde l'utilisateur sur sa page d'origine (souvent /esbtp/resultats/etudiant/...)
                // et signale qu'un coefficient est manquant. La page result detail détecte le
                // session('coefficient_missing_context') et auto-ouvre #studentCoeffModal in-page
                // au lieu de rediriger vers evaluations.index?open_coefficients=1 (rupture UX).
                return redirect()->back()
                    ->with('error', $message.' Configurez les coefficients avant de continuer.')
                    ->with('coefficient_missing_context', ['runtime' => true, 'message' => $message]);
            }
        }

        return parent::render($request, $e);
    }
}
