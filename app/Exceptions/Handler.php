<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use RuntimeException;
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
        if ($e instanceof TokenMismatchException) {
            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('warning', 'Votre session a expiré. Veuillez réessayer.');
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

                return redirect()->route('esbtp.evaluations.index', ['open_coefficients' => 1])
                    ->with('error', $message.' Configurez les coefficients avant de continuer.');
            }
        }

        return parent::render($request, $e);
    }
}
