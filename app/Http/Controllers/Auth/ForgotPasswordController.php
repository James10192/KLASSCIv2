<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
// Commentons cette ligne car le trait n'est pas trouvé
// use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    // Commentons cette ligne car le trait n'est pas trouvé
    // use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Affiche le formulaire de demande de réinitialisation du mot de passe.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Envoie un lien de réinitialisation du mot de passe à l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);
        $email = mb_strtolower(trim((string) $request->input('email')));

        // Accepter aussi l'e-mail d'un parent, car le parent et l'étudiant
        // utilisent exactement le même compte. On n'affiche jamais si une
        // adresse est connue : protection anti-énumération.
        // Un parent peut être l'adresse principale d'un enfant ET le
        // contact familial d'autres enfants. Parcourir tous les comptes
        // associés, pas seulement celui qui possède users.email.
        $ids = \App\Models\User::query()->where('email', $email)->pluck('id')
            ->merge(\App\Models\ESBTPEtudiant::query()
                ->where('email_personnel', $email)
                ->orWhere('email', $email)
                ->orWhereHas('parents', fn ($q) => $q->where('email', $email))
                ->pluck('user_id'))
            ->filter()->unique()->take(10);
        $comptes = \App\Models\User::whereIn('id', $ids)->get();

        foreach ($comptes->take(10) as $compte) {
            if (! $compte->is_active
                || ! app(\App\Services\Admissions\StudentAccessInvitation::class)
                    ->assurerEmailPrincipal($compte)) {
                continue;
            }
            try {
                $this->broker()->sendResetLink(['email' => $compte->email]);
            } catch (\Throwable $e) {
                Log::warning('Réinitialisation du compte partagé non envoyée', [
                    'user_id' => $compte->id, 'exception' => $e::class,
                ]);
            }
        }

        return back()->with('status', 'Si cette adresse est associée à un compte KLASSCI, un lien sécurisé a été envoyé à l’étudiant et aux parents rattachés.');
    }

    /**
     * Valide l'adresse e-mail pour la demande de réinitialisation du mot de passe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
    }

    /**
     * Obtient les informations d'identification nécessaires pour la demande de réinitialisation du mot de passe.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only('email');
    }

    /**
     * Obtient le courtier de réinitialisation du mot de passe à utiliser lors de la réinitialisation.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Obtient la réponse pour une demande de réinitialisation de mot de passe réussie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkResponse(Request $request, $response)
    {
        return back()->with('status', trans($response));
    }

    /**
     * Obtient la réponse pour une demande de réinitialisation de mot de passe échouée.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        throw ValidationException::withMessages([
            'email' => [trans($response)],
        ]);
    }
} 